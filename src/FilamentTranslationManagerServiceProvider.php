<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager;

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationCellEditor;

class FilamentTranslationManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-manager';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)->hasViews()->hasTranslations();
    }

    public function packageBooted(): void
    {
        $supportedLocales = array_unique(array_filter([
            config('app.locale'),
            config('app.fallback_locale'),
        ]));

        FilamentTranslationManager::setLocales($supportedLocales);

        Livewire::component('filament-translation-cell-editor', TranslationCellEditor::class);

        Blade::anonymousComponentNamespace('filament-translation-manager::components.translation-cell', 'tcm');
    }
}
