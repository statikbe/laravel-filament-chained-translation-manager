<?php

namespace Statikbe\FilamentTranslationManager;

use Spatie\LaravelPackageTools\Package;
use Filament\PluginServiceProvider;
use Statikbe\FilamentTranslationManager\Commands\FilamentTranslationManagerCommand;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;

class FilamentTranslationManagerServiceProvider extends PluginServiceProvider
{
    protected array $pages = [
        TranslationManagerPage::class,
    ];

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('filament-translation-manager')
            ->hasViews()
            ->hasTranslations()
            ->hasConfigFile()
        ;
    }

    public function packageBooted(): void
    {
        $supportedLocales = config(
            'filament-translation-manager.supported_locales',
            config('app.supported_locales',['en'])
        );

        FilamentTranslationManager::setLocales($supportedLocales);
    }
}
