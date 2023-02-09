<?php

namespace Statikbe\FilamentTranslationManager;

use Filament\PluginServiceProvider;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationEditForm;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentTranslationManagerServiceProvider extends PluginServiceProvider
{
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
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-translation-manager.php', 'filament-translation-manager');

        $supportedLocales = config(
            'filament-translation-manager.supported_locales',
            config('app.supported_locales', ['en'])
        );

        FilamentTranslationManager::setLocales($supportedLocales);

        Livewire::component(TranslationManagerPage::class::getName(), TranslationManagerPage::class);
        Livewire::component('translation-edit-form', TranslationEditForm::class);
        Livewire::component(TranslationStatusWidget::class::getName(), TranslationStatusWidget::class);
    }

    protected function getPages(): array
    {
        $pages = [];

        if (config('filament-translation-manager.enabled', false)) {
            $pages['translation-manager-page'] = TranslationManagerPage::class;
        }

        return $pages;
    }

    protected function getWidgets(): array
    {
        $widgets = [];
        if (config('filament-translation-manager.widget.enabled', false)) {
            $widgets[] = TranslationStatusWidget::class;
        }

        return $widgets;
    }
}
