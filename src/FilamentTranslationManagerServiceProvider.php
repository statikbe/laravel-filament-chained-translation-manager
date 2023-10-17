<?php

namespace Statikbe\FilamentTranslationManager;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationEditForm;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentTranslationManagerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-translation-manager';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-translation-manager.php', 'filament-translation-manager');

        $supportedLocales = config('filament-translation-manager.locales', config('filament-translation-manager.supported_locales'));

        if (empty($supportedLocales)) {
            $supportedLocales = [
                config('app.locale'),
                config('app.fallback_locale'),
            ];
        }

        FilamentTranslationManager::setLocales($supportedLocales);

        Livewire::component('translation-manager-page', TranslationManagerPage::class);
        Livewire::component('translation-edit-form', TranslationEditForm::class);
        Livewire::component('translation-status', TranslationStatusWidget::class);
    }
}
