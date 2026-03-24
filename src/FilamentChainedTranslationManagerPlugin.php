<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Statikbe\FilamentTranslationManager\Concerns\HasAiTranslation;
use Statikbe\FilamentTranslationManager\Concerns\HasLocaleConfiguration;
use Statikbe\FilamentTranslationManager\Concerns\HasNavigationConfiguration;
use Statikbe\FilamentTranslationManager\Concerns\HasWidgetConfiguration;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentChainedTranslationManagerPlugin implements Plugin
{
    use HasLocaleConfiguration;
    use HasNavigationConfiguration;
    use HasWidgetConfiguration;
    use HasAiTranslation;

    protected ?string $gate = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'filament-chained-translation-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([TranslationManagerPage::class]);

        if ($this->widgetEnabled) {
            $panel->widgets([TranslationStatusWidget::class]);
        }
    }

    public function boot(Panel $panel): void {}

    /**
     * Gate name used to authorize access to the translation manager page.
     */
    public function gate(?string $gate): static
    {
        $this->gate = $gate;

        return $this;
    }

    public function getGate(): ?string
    {
        return $this->gate;
    }
}
