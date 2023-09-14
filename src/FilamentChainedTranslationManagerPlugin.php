<?php

namespace Statikbe\FilamentTranslationManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentChainedTranslationManagerPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-chained-translation-manager';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                TranslationManagerPage::class,
            ])
            ->widgets([
                TranslationStatusWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
