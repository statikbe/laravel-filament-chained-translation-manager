<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager;

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationCellEditor;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentTranslationManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-manager';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews()->hasTranslations();
    }

    public function packageBooted(): void
    {
        // Populate the static locale list from the supported locales config or app defaults.
        // This is used as a fallback when locales are not set via the plugin's fluent API.
        $supportedLocales = config('filament-translation-manager.locales');

        if ($supportedLocales === null || $supportedLocales === []) {
            $supportedLocales = array_unique(array_filter([
                config('app.locale'),
                config('app.fallback_locale'),
            ]));
        }

        FilamentTranslationManager::setLocales($supportedLocales);

        Livewire::component('translation-manager-page', TranslationManagerPage::class);
        Livewire::component('translation-status', TranslationStatusWidget::class);
        Livewire::component('filament-translation-cell-editor', TranslationCellEditor::class);

        Blade::anonymousComponentNamespace('filament-translation-manager::components.translation-cell', 'tcm');
    }
}
