<?php

use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationEditForm;

function editForm(string $group = 'test', array $locales = ['en', 'nl'])
{
    return Livewire::test(TranslationEditForm::class, [
        'group' => $group,
        'translationKey' => 'hello',
        'translations' => ['en' => 'Hello', 'nl' => 'Hallo'],
        'locales' => $locales,
    ]);
}

it('saves a translation for a valid locale and group', function () {
    $this->actingAsTranslator();

    editForm()
        ->set('translations.nl', 'Hallo daar')
        ->call('save', 'nl')
        ->assertOk()
        ->assertDispatched(TranslationEditForm::EVENT_TRANSLATIONS_SAVED);

    $file = $this->customTranslationFile('nl', 'test');
    expect($file)->toBeFile()
        ->and(require $file)->toBe(['hello' => 'Hallo daar']);
});

it('rejects locales that are not configured or not valid', function (string $locale) {
    $this->actingAsTranslator();

    editForm()
        ->set("translations.{$locale}", 'injected')
        ->call('save', $locale)
        ->assertStatus(422);

    expect(glob($this->customLangPath.'/*/*'))->toBe([])
        ->and(glob($this->tempPath.'/*.php'))->toBe([]);
})->with([
    'path traversal' => '../x',
    'not configured' => 'xx',
]);

it('rejects a configured locale that was not passed to the form', function () {
    $this->actingAsTranslator();

    editForm(locales: ['en'])
        ->set('translations.nl', 'Hallo daar')
        ->call('save', 'nl')
        ->assertStatus(422);

    expect($this->customTranslationFile('nl', 'test'))->not->toBeFile();
});

it('drops locales that are not configured when mounting', function () {
    $this->actingAsTranslator();

    editForm(locales: ['en', "x'y", '../x'])
        ->assertSet('locales', ['en']);
});

it('does not accept a non-string translation', function () {
    $this->actingAsTranslator();

    editForm()
        ->set('translations.nl', ['not', 'a', 'string'])
        ->assertSet('translations.nl', 'Hallo')
        ->call('save', 'nl')
        ->assertOk()
        ->assertNotDispatched(TranslationEditForm::EVENT_TRANSLATIONS_SAVED);

    expect($this->customTranslationFile('nl', 'test'))->not->toBeFile();
});

it('does not allow the client to change locked properties', function (string $property, mixed $value) {
    $this->actingAsTranslator();

    editForm()->set($property, $value);
})->with([
    'group' => ['group', '../../evil'],
    'translationKey' => ['translationKey', 'other'],
    'locales' => ['locales', ['en', '../x']],
    'initialTranslations' => ['initialTranslations', ['nl' => 'x']],
])->throws(CannotUpdateLockedPropertyException::class);

it('forbids saving for a user without the gate', function () {
    $this->actingAsTranslator(canTranslate: false);

    editForm()
        ->set('translations.nl', 'Hallo daar')
        ->call('save', 'nl')
        ->assertForbidden();

    expect($this->customTranslationFile('nl', 'test'))->not->toBeFile();
});

it('rejects saving a group that is ignored', function () {
    $this->actingAsTranslator();

    editForm(group: 'secret')
        ->set('translations.nl', 'Geheim')
        ->call('save', 'nl')
        ->assertStatus(422);

    expect($this->customTranslationFile('nl', 'secret'))->not->toBeFile();
});

it('rejects saving a group that does not exist', function () {
    $this->actingAsTranslator();

    editForm(group: '../../evil')
        ->set('translations.nl', 'x')
        ->call('save', 'nl')
        ->assertStatus(422);

    expect(glob($this->customLangPath.'/*/*'))->toBe([]);
});

it('renders the locale as a JavaScript string in the Alpine component', function () {
    $this->actingAsTranslator();

    editForm()
        ->assertSeeHtml("locale: 'en',")
        ->assertSeeHtml("locale: 'nl',")
        ->assertDontSeeHtml("locale: '{{");
});

it('never renders a locale containing a quote', function () {
    $this->actingAsTranslator();

    editForm(locales: ['en', "x'-alert(1)-'"])
        ->assertDontSeeHtml('alert(1)');
});

it('shows a notification instead of an error page when the custom file cannot be read', function (string $group, string $file, string $contents) {
    $this->actingAsTranslator();

    file_put_contents($this->langPath.'/nl.json', '{"hello": "Hallo"}');
    $customFile = $this->customLangPath.'/'.$file;
    file_put_contents($customFile, $contents);

    editForm(group: $group)
        ->set('translations.nl', 'Hallo daar')
        ->call('save', 'nl')
        ->assertOk()
        ->assertNotified(trans('filament-translation-manager::messages.saving_translation_failed'))
        ->assertNotDispatched(TranslationEditForm::EVENT_TRANSLATIONS_SAVED)
        ->assertSet('translations.nl', 'Hallo daar')
        ->assertSet('initialTranslations.nl', 'Hallo');

    expect(file_get_contents($customFile))->toBe($contents);
})->with([
    'invalid JSON file' => ['json-file', 'nl.json', '{"a": "b",}'],
    'PHP file that returns a string' => ['test', 'nl/test.php', "<?php return 'not an array';"],
]);

it('applies a saved validation attribute name straight away', function () {
    $this->actingAsTranslator();

    Livewire::test(TranslationEditForm::class, [
        'group' => 'validation',
        'translationKey' => 'attributes.email',
        'translations' => ['en' => null, 'nl' => null],
        'locales' => ['en', 'nl'],
    ])
        ->set('translations.en', 'e-mail address')
        ->call('save', 'en')
        ->assertOk()
        ->assertDispatched(TranslationEditForm::EVENT_TRANSLATIONS_SAVED);

    $validator = Validator::make(['email' => ''], ['email' => 'required']);

    expect($validator->errors()->first('email'))->toContain('e-mail address');
});
