<?php

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;

it('only loads the configured locales and the groups that are not ignored', function () {
    $this->actingAsTranslator();

    Livewire::test(TranslationManagerPage::class)
        ->assertOk()
        ->assertSet('locales', ['en', 'nl'])
        ->assertSet('groups', ['test'])
        ->assertSet('selectedLocales', ['en', 'nl'])
        ->assertSee('Hallo');
});

it('drops unknown groups from the query string', function () {
    $this->actingAsTranslator();

    Livewire::withQueryParams(['selectedGroups' => ['test', 'secret', '../../x']])
        ->test(TranslationManagerPage::class)
        ->assertSet('selectedGroups', ['test']);
});

it('drops unknown or invalid locales and groups set by the client', function () {
    $this->actingAsTranslator();

    Livewire::test(TranslationManagerPage::class)
        ->set('selectedLocales', ['nl', 'xx', '../x', "x'-alert(1)-'", ['nested']])
        ->set('selectedGroups', ['test', 'secret', '../../x'])
        ->assertSet('selectedLocales', ['nl'])
        ->assertSet('selectedGroups', ['test'])
        ->assertDontSeeHtml('alert(1)')
        ->call('submitFilters')
        ->assertSet('selectedLocales', ['nl'])
        ->assertSet('selectedGroups', ['test']);
});

it('drops tampered locales before the edit forms are rendered', function () {
    $this->actingAsTranslator();

    Livewire::test(TranslationManagerPage::class)
        ->set('selectedLocales', ['en', "x'-alert(1)-'"])
        ->assertSeeHtml("locale: 'en',")
        ->assertDontSeeHtml('alert(1)');
});

it('does not allow the client to change the server-side lists', function (string $property) {
    $this->actingAsTranslator();

    Livewire::test(TranslationManagerPage::class)
        ->set($property, ['../../x']);
})->with(['groups', 'locales'])->throws(CannotUpdateLockedPropertyException::class);

it('forbids a user without the gate', function () {
    $this->actingAsTranslator(canTranslate: false);

    Livewire::test(TranslationManagerPage::class)->assertForbidden();
});

it('hides the navigation for a user without the gate', function () {
    $this->actingAsTranslator(canTranslate: false);
    expect(TranslationManagerPage::shouldRegisterNavigation())->toBeFalse();

    $this->actingAsTranslator();
    expect(TranslationManagerPage::shouldRegisterNavigation())->toBeTrue();
});
