<?php

use Livewire\Livewire;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationEditForm;
use Statikbe\FilamentTranslationManager\Support\TranslationHtmlSanitizer;

function saveTranslation(string $value, string $locale = 'nl'): mixed
{
    return Livewire::test(TranslationEditForm::class, [
        'group' => 'test',
        'translationKey' => 'hello',
        'translations' => ['en' => 'Hello', 'nl' => 'Hallo'],
        'locales' => ['en', 'nl'],
    ])
        ->set("translations.{$locale}", $value)
        ->call('save', $locale)
        ->assertOk();
}

it('strips script on save', function () {
    $this->actingAsTranslator();

    saveTranslation('KIK<script>alert(1)</script>')
        ->assertSet('translations.nl', 'KIK')
        ->assertSet('initialTranslations.nl', 'KIK');

    $contents = file_get_contents($this->customTranslationFile('nl', 'test'));

    expect($contents)->not->toContain('<script')
        ->and($contents)->not->toContain('alert(')
        ->and(require $this->customTranslationFile('nl', 'test'))->toBe(['hello' => 'KIK']);
});

it('strips event handlers and dangerous link schemes on save', function (string $value, string $forbidden) {
    $this->actingAsTranslator();

    saveTranslation($value);

    $stored = (require $this->customTranslationFile('nl', 'test'))['hello'];

    expect(strtolower($stored))->not->toContain($forbidden);
})->with([
    'event handler' => ['<img src=x onerror="x()">', 'onerror'],
    'javascript link' => ['<a href="javascript:x()">x</a>', 'javascript:'],
    'obfuscated javascript link' => ['<a href="  JaVaScRiPt:x()">x</a>', 'javascript:'],
    'data link' => ['<a href="data:text/html,<script>x()</script>">x</a>', 'data:'],
    'style attribute' => ['<span style="background:url(x)">x</span>', 'style'],
    'iframe' => ['<iframe src="https://evil.test"></iframe>', 'iframe'],
    'svg' => ['<svg onload="x()"></svg>', 'onload'],
]);

it('keeps the allowed markup and forces rel on links', function () {
    $this->actingAsTranslator();

    saveTranslation('<a href="https://statik.be">ok</a> <strong>b</strong> <a href="/privacy">privacy</a>');

    $stored = (require $this->customTranslationFile('nl', 'test'))['hello'];

    expect($stored)->toContain('href="https://statik.be"')
        ->toContain('href="/privacy"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('>ok</a>')
        ->toContain('<strong>b</strong>');
});

it('leaves plain text and placeholders intact', function (string $value) {
    $this->actingAsTranslator();

    saveTranslation($value);

    expect((require $this->customTranslationFile('nl', 'test'))['hello'])->toBe($value);
})->with([
    'placeholder' => 'Hallo :name',
    'special characters' => "Tom & Jerry's \"show\" @ 1+1=2 :count",
    'less than sign' => 'a < b > c',
]);

it('warns the user when HTML was removed', function () {
    $this->actingAsTranslator();

    saveTranslation('KIK<script>alert(1)</script>')
        ->assertDispatched(TranslationEditForm::EVENT_TRANSLATIONS_SAVED)
        ->assertNotified(trans('filament-translation-manager::messages.saved_translation_html_removed'));
});

it('does not warn the user when the sanitizer only normalized the HTML', function (string $value) {
    $this->actingAsTranslator();

    saveTranslation($value)
        ->assertNotified(trans('filament-translation-manager::messages.saved_translation'));
})->with([
    'plain text' => 'Hallo :name',
    'encoded characters' => "<b>it's @ 1=1 &amp; é</b>",
    'link without rel' => "<a href='https://statik.be' target=_blank>ok</a>",
    'link with another rel' => '<a href="/privacy" rel="opener">privacy</a>',
    'void and unclosed tags' => '<UL><li>a<br><li>b<br/></UL><b>c',
]);

it('detects removed content', function (string $value, bool $removed) {
    $sanitizer = app(TranslationHtmlSanitizer::class);

    expect(TranslationHtmlSanitizer::removedContent($value, $sanitizer->sanitize($value)))->toBe($removed);
})->with([
    'script' => ['a<script>x()</script>', true],
    'event handler' => ['<b onclick="x()">a</b>', true],
    'javascript link' => ['<a href="javascript:x()">a</a>', true],
    'style' => ['<span style="color:red">a</span>', true],
    'comment' => ['<!-- a --><b>b</b>', true],
    'unknown element' => ['<table><tr><td>a</td></tr></table>', true],
    'allowed markup' => ['<p><strong>a</strong> <a href="mailto:a@b.be" title="Mail">b</a></p>', false],
]);

it('stores the raw value when sanitizing is disabled', function () {
    $this->actingAsTranslator();
    config()->set('filament-translation-manager.sanitize_html.enabled', false);

    saveTranslation('KIK<script>alert(1)</script>');

    expect((require $this->customTranslationFile('nl', 'test'))['hello'])->toBe('KIK<script>alert(1)</script>');
});

it('uses the allowed elements from the config', function () {
    config()->set('filament-translation-manager.sanitize_html.allowed_elements', ['strong' => []]);

    expect(app(TranslationHtmlSanitizer::class)->sanitize('<strong>a</strong><em>b</em>'))->toBe('<strong>a</strong>');
});

it('does not truncate long translations', function () {
    $value = '<p>'.str_repeat('a', 30000).'</p>';

    expect(app(TranslationHtmlSanitizer::class)->sanitize($value))->toBe($value);
});

it('sanitizes the stored custom translations with the artisan command', function () {
    $file = $this->customTranslationFile('nl', 'test');
    file_put_contents($file, "<?php return ['hello' => 'KIK<script>alert(1)</script>', 'plain' => 'Tom & Jerry'];");
    file_put_contents($this->customLangPath.'/en.json', '{"Bye": "<img src=x onerror=\"x()\">Bye"}');

    $this->artisan('filament-translation-manager:sanitize')
        ->expectsOutputToContain('[nl] test.hello')
        ->expectsOutputToContain('[en] json-file.Bye')
        ->expectsOutputToContain('2 translation(s) sanitized.')
        ->assertSuccessful();

    expect(require $file)->toBe(['hello' => 'KIK', 'plain' => 'Tom & Jerry'])
        ->and(json_decode(file_get_contents($this->customLangPath.'/en.json'), true))->toBe(['Bye' => 'Bye']);
});

it('does not save anything with the --dry-run option', function () {
    $file = $this->customTranslationFile('nl', 'test');
    $contents = "<?php return ['hello' => 'KIK<script>alert(1)</script>'];";
    file_put_contents($file, $contents);

    $this->artisan('filament-translation-manager:sanitize', ['--dry-run' => true])
        ->expectsOutputToContain('[nl] test.hello')
        ->expectsOutputToContain('1 translation(s) would be sanitized.')
        ->assertSuccessful();

    expect(file_get_contents($file))->toBe($contents);
});

it('reports when there is nothing to sanitize', function () {
    file_put_contents($this->customTranslationFile('nl', 'test'), "<?php return ['hello' => '<strong>Hallo</strong> :name'];");

    $this->artisan('filament-translation-manager:sanitize')
        ->expectsOutputToContain('No translations contain unsafe HTML.')
        ->assertSuccessful();
});
