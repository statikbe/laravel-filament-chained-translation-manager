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

it('encodes a less-than sign before a placeholder, so the replacement cannot start a tag', function () {
    $this->actingAsTranslator();

    saveTranslation('Hi <:name> and </:tag>');

    $stored = (require $this->customTranslationFile('nl', 'test'))['hello'];

    expect($stored)->not->toContain('<:')
        ->not->toContain('</:')
        ->and(__('test.hello', ['name' => 'img src=x onerror=alert(1)'], 'nl'))->not->toContain('<img');
});

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

/**
 * Checks that the HTML can't run a script, load a resource or change the page when it's printed unescaped.
 */
function expectSafeHtml(string $html): void
{
    $html = strtolower($html);

    expect($html)->not->toMatch('/\son[a-z]+\s*=/')
        ->not->toContain('javascript:')
        ->not->toContain('vbscript:')
        ->not->toContain('data:')
        ->not->toContain('style')
        ->not->toContain('<!--')
        ->not->toContain('<![cdata[');

    foreach (['script', 'img', 'svg', 'math', 'iframe', 'base', 'meta', 'form', 'input', 'textarea', 'template', 'noscript', 'object', 'embed'] as $element) {
        expect($html)->not->toContain('<'.$element);
    }
}

it('removes XSS vectors', function (string $value) {
    $sanitizer = app(TranslationHtmlSanitizer::class);
    $sanitized = $sanitizer->sanitize($value);

    expectSafeHtml($sanitized);
    expect(TranslationHtmlSanitizer::removedContent($value, $sanitized))->toBeTrue()
        ->and($sanitizer->sanitize($sanitized))->toBe($sanitized);
})->with([
    'uppercase script' => '<SCRIPT>x()</SCRIPT>',
    'script with slash' => '<script/xss>x()</script>',
    'nested script' => '<scr<script>ipt>x()</script>',
    'double less-than' => '<<script>script>x()<</script>/script>',
    'null byte in tag' => "<scri\0pt>x()</script>",
    'img with slashes' => '<img/src=x/onerror=x()>',
    'event handler on allowed element' => '<b title="x" onmouseover="x()">x</b>',
    'event handler on link' => '<a href="https://statik.be" onclick="x()">x</a>',
    'javascript link with tab' => "<a href=\"jav\tascript:x()\">x</a>",
    'javascript link with encoded tab' => '<a href="jav&#x09;ascript:x()">x</a>',
    'javascript link with entity' => '<a href="&#106;avascript:x()">x</a>',
    'vbscript link' => '<a href="vbscript:x()">x</a>',
    'base64 data link' => '<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>',
    'script in svg' => '<svg><script>x()</script></svg>',
    'math namespace' => '<math><mtext><b>x</b></mtext></math>',
    'noscript mutation' => '<noscript><p title="</noscript><img src=x onerror=x()>"></noscript>',
    'style element' => '<style>b{background:url(x)}</style><b>x</b>',
    'template' => '<template><script>x()</script></template>',
    'textarea' => '<textarea><script>x()</script></textarea>',
    'base' => '<base href="https://evil.test">',
    'meta refresh' => '<meta http-equiv="refresh" content="0;url=https://evil.test">',
    'form with autofocus' => '<form action="https://evil.test"><input autofocus onfocus=x()></form>',
    'comment hiding a script' => '<!--><script>x()</script>-->',
    'cdata' => '<![CDATA[<script>x()</script>]]>',
    'processing instruction' => '<?php echo 1; ?>',
    'unclosed attribute' => '<a href="https://statik.be',
]);

it('keeps an escaped script in an attribute escaped', function () {
    $sanitized = app(TranslationHtmlSanitizer::class)->sanitize('<a title="&quot;><script>x()</script>">x</a>');

    expectSafeHtml($sanitized);
    expect($sanitized)->toContain('title="&#34;&gt;&lt;script&gt;');
});

it('keeps safe HTML, only normalizing it', function (string $value, string $expected) {
    $sanitized = app(TranslationHtmlSanitizer::class)->sanitize($value);

    expect($sanitized)->toBe($expected)
        ->and(TranslationHtmlSanitizer::removedContent($value, $sanitized))->toBeFalse();
})->with([
    'pluralization' => ['{0} <b>none</b>|[1,*] <b>:count</b> items', '{0} <b>none</b>|[1,*] <b>:count</b> items'],
    'placeholders in text' => ['<b>:name :Name :NAME</b>', '<b>:name :Name :NAME</b>'],
    'placeholder in a relative link' => ['<a href="/:locale/page">x</a>', '<a href="/:locale/page" rel="noopener noreferrer">x</a>'],
    'placeholder in an absolute link' => ['<a href="https://statik.be/:slug">x</a>', '<a href="https://statik.be/:slug" rel="noopener noreferrer">x</a>'],
    'placeholder in a mailto link' => ['<a href="mailto::email">x</a>', '<a href="mailto::email" rel="noopener noreferrer">x</a>'],
    'anchor link' => ['<a href="#top">top</a>', '<a href="#top" rel="noopener noreferrer">top</a>'],
    'uppercase scheme' => ['<a href="HTTPS://statik.be">x</a>', '<a href="https://statik.be" rel="noopener noreferrer">x</a>'],
    'misnested tags' => ['<b><i>x</b></i>', '<b><i>x</i></b>'],
    'escaped script text' => ['<b>&lt;script&gt;</b>', '<b>&lt;script&gt;</b>'],
    'greater-than in an attribute' => ['<a title="a>b" href="https://statik.be">x</a>', '<a title="a&gt;b" href="https://statik.be" rel="noopener noreferrer">x</a>'],
    'script in a link query' => ['<a href="https://statik.be/?q=<script>">x</a>', '<a href="https://statik.be/?q&#61;&lt;script&gt;" rel="noopener noreferrer">x</a>'],
    'unicode' => ['<b>😀 émoji العربية</b>', '<b>😀 émoji العربية</b>'],
    'new lines' => ["<p>a</p>\n<p>b</p>", "<p>a</p>\n<p>b</p>"],
    'superscript' => ['Register your samples for <sup>14</sup>C dating', 'Register your samples for <sup>14</sup>C dating'],
    'subscript' => ['H<sub>2</sub>O', 'H<sub>2</sub>O'],
    'small' => ['Price <small>excl. VAT</small>', 'Price <small>excl. VAT</small>'],
    'strikethrough' => ['<s>€49</s> €39', '<s>€49</s> €39'],
    'deleted' => ['<del>old</del> new', '<del>old</del> new'],
    'mark' => ['a <mark>highlighted</mark> word', 'a <mark>highlighted</mark> word'],
    'abbreviation' => ['<abbr title="Value Added Tax">VAT</abbr>', '<abbr title="Value Added Tax">VAT</abbr>'],
    'code' => ['Run <code>php artisan migrate</code>', 'Run <code>php artisan migrate</code>'],
    'blockquote' => ['<blockquote>:quote</blockquote>', '<blockquote>:quote</blockquote>'],
    'headings' => ['<h1>a</h1><h2>b</h2><h3>c</h3><h4>d</h4><h5>e</h5><h6>f</h6>', '<h1>a</h1><h2>b</h2><h3>c</h3><h4>d</h4><h5>e</h5><h6>f</h6>'],
]);

it('leaves text without tags unchanged', function (string $value) {
    expect(app(TranslationHtmlSanitizer::class)->sanitize($value))->toBe($value);
})->with([
    'less than a number' => '1<2',
    'heart' => '<3 you',
    'arrows' => '<- back -> a => b',
    'less than or equal' => '<= 5',
    'double less-than' => '<<',
    'empty brackets' => '<>',
    'escaped script text' => '&lt;script&gt;',
    'entity' => '&nbsp;a',
    'pluralization' => '{0} none|[1,*] :count items',
    'new lines' => "line 1\nline 2",
    'empty' => '',
]);

it('treats a less-than sign followed by a letter as a tag, like a browser does', function () {
    expect(app(TranslationHtmlSanitizer::class)->sanitize('a<b'))->toBe('a');
});

it('keeps a link that starts with a placeholder, which the application replaces with a URL', function (string $value, string $expected) {
    $this->actingAsTranslator();

    saveTranslation($value)
        ->assertNotified(trans('filament-translation-manager::messages.saved_translation'));

    expect((require $this->customTranslationFile('nl', 'test'))['hello'])->toBe($expected)
        ->and(__('test.hello', ['url' => 'https://statik.be', 'slug' => 'about'], 'nl'))->toContain('href="https://statik.be');
})->with([
    'quoted' => ['<a href=":url">x</a>', '<a href=":url" rel="noopener noreferrer">x</a>'],
    'single quoted' => ["<a href=':url'>x</a>", '<a href=":url" rel="noopener noreferrer">x</a>'],
    'unquoted' => ['<a href=:url>x</a>', '<a href=":url" rel="noopener noreferrer">x</a>'],
    'with a path, query and fragment' => ['<a href=":url/:slug?a=1#top">x</a>', '<a href=":url/:slug?a&#61;1#top" rel="noopener noreferrer">x</a>'],
    'several links' => ['<a href=":url">x</a> <a href=":url">y</a>', '<a href=":url" rel="noopener noreferrer">x</a> <a href=":url" rel="noopener noreferrer">y</a>'],
]);

it('still removes unsafe HTML next to a placeholder link', function () {
    $sanitized = app(TranslationHtmlSanitizer::class)->sanitize('<a href=":url" onclick="x()">x</a><img src=x href=:url onerror=x()>');

    expectSafeHtml($sanitized);
    expect($sanitized)->toBe('<a href=":url" rel="noopener noreferrer">x</a>');
});

it('does not keep a placeholder link when https links are not allowed', function () {
    config()->set('filament-translation-manager.sanitize_html.allowed_link_schemes', ['mailto']);

    expect(app(TranslationHtmlSanitizer::class)->sanitize('<a href=":url">x</a>'))->toBe('<a rel="noopener noreferrer">x</a>');
});

it('does not warn when a sanitized translation is saved again', function () {
    $this->actingAsTranslator();

    $sanitized = app(TranslationHtmlSanitizer::class)->sanitize("<a href='https://statik.be'>it's</a> <:name");

    saveTranslation($sanitized)
        ->assertNotified(trans('filament-translation-manager::messages.saved_translation'));

    expect((require $this->customTranslationFile('nl', 'test'))['hello'])->toBe($sanitized);
});

it('sanitizes JSON translations on save', function () {
    $this->actingAsTranslator();
    file_put_contents($this->langPath.'/en.json', '{"Welcome": "Welcome"}');

    Livewire::test(TranslationEditForm::class, [
        'group' => 'json-file',
        'translationKey' => 'Welcome',
        'translations' => ['en' => null, 'nl' => null],
        'locales' => ['en', 'nl'],
    ])
        ->set('translations.en', '<b>Welcome</b><img src=x onerror=x()>')
        ->call('save', 'en')
        ->assertOk();

    expect(json_decode(file_get_contents($this->customLangPath.'/en.json'), true))->toBe(['Welcome' => '<b>Welcome</b>']);
});
