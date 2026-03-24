<?php

namespace Statikbe\FilamentTranslationManager;

use BackedEnum;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Statikbe\FilamentTranslationManager\Pages\TranslationManagerPage;
use Statikbe\FilamentTranslationManager\Widgets\TranslationStatusWidget;

class FilamentChainedTranslationManagerPlugin implements Plugin
{
    protected array $locales = [];

    protected ?string $sourceLocale = null;

    protected ?string $gate = null;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected string|BackedEnum|Htmlable|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected array $ignoreGroups = [];

    protected bool $widgetEnabled = false;

    protected ?string $widgetGate = null;

    protected ?int $widgetSort = null;

    protected bool $aiEnabled = false;

    protected ?string $aiDriver = null;

    protected bool $aiRowAction = true;

    protected bool $aiModalAction = true;

    protected bool $aiHeaderAction = true;

    protected bool $aiBulkAction = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
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

    // ─── Fluent setters ──────────────────────────────────────────────────────

    /**
     * Set the locales available in the translation manager.
     * Defaults to the locales registered in the service provider.
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Set the source locale (the authoritative language to translate from).
     * Defaults to config('app.locale').
     */
    public function sourceLocale(string $locale): static
    {
        $this->sourceLocale = $locale;

        return $this;
    }

    /**
     * Gate name used to authorize access to the translation manager page.
     */
    public function gate(?string $gate): static
    {
        $this->gate = $gate;

        return $this;
    }

    /**
     * Navigation group for the translation manager page.
     */
    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Navigation sort order.
     */
    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Navigation icon. Pass null to hide the icon.
     */
    public function navigationIcon(string|BackedEnum|Htmlable|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /**
     * Translation groups to hide from the UI.
     */
    public function ignoreGroups(array $groups): static
    {
        $this->ignoreGroups = $groups;

        return $this;
    }

    /**
     * Configure the dashboard widget that shows translation completion stats.
     *
     * @param  bool  $enabled  Whether to register the widget.
     * @param  string|null  $gate  Optional gate to authorize widget visibility.
     * @param  int|null  $sort  Widget sort order.
     */
    public function widget(bool $enabled = true, ?string $gate = null, ?int $sort = null): static
    {
        $this->widgetEnabled = $enabled;
        $this->widgetGate = $gate;
        $this->widgetSort = $sort;

        return $this;
    }

    /**
     * Enable AI translation features.
     *
     * Requires `statikbe/laravel-ai-translation` to be installed.
     *
     * @param  bool  $enabled  Master switch for AI features.
     * @param  string|null  $driver  AI driver override (null = use ai-translation config default).
     * @param  bool  $rowAction  Show per-row "AI Fill" action button.
     * @param  bool  $modalAction  Show "AI Fill All" button inside the edit modal.
     * @param  bool  $headerAction  Show "Translate All Missing" button in the table toolbar.
     * @param  bool  $bulkAction  Show "AI Translate Selected" bulk action.
     */
    public function aiTranslation(
        bool $enabled = true,
        ?string $driver = null,
        bool $rowAction = true,
        bool $modalAction = true,
        bool $headerAction = true,
        bool $bulkAction = true,
    ): static {
        $this->aiEnabled = $enabled;
        $this->aiDriver = $driver;
        $this->aiRowAction = $rowAction;
        $this->aiModalAction = $modalAction;
        $this->aiHeaderAction = $headerAction;
        $this->aiBulkAction = $bulkAction;

        return $this;
    }

    // ─── Getters ─────────────────────────────────────────────────────────────

    public function getLocales(): array
    {
        if (!empty($this->locales)) {
            return $this->locales;
        }

        return FilamentTranslationManager::getLocales();
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale ?? config('app.locale', 'en');
    }

    public function getGate(): ?string
    {
        return $this->gate;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? trans('filament-translation-manager::messages.navigation_group');
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->navigationIcon;
    }

    public function getIgnoreGroups(): array
    {
        return $this->ignoreGroups;
    }

    public function isWidgetEnabled(): bool
    {
        return $this->widgetEnabled;
    }

    public function getWidgetGate(): ?string
    {
        return $this->widgetGate;
    }

    public function getWidgetSort(): ?int
    {
        return $this->widgetSort;
    }

    /**
     * Whether AI translation is enabled and the AI translation package is installed.
     */
    public function isAiEnabled(): bool
    {
        return $this->aiEnabled && class_exists(\Statikbe\AiTranslation\AiTranslationService::class);
    }

    public function getAiDriver(): ?string
    {
        return $this->aiDriver;
    }

    public function hasAiRowAction(): bool
    {
        return $this->isAiEnabled() && $this->aiRowAction;
    }

    public function hasAiModalAction(): bool
    {
        return $this->isAiEnabled() && $this->aiModalAction;
    }

    public function hasAiHeaderAction(): bool
    {
        return $this->isAiEnabled() && $this->aiHeaderAction;
    }

    public function hasAiBulkAction(): bool
    {
        return $this->isAiEnabled() && $this->aiBulkAction;
    }
}
