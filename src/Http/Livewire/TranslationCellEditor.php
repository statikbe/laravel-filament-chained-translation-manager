<?php

namespace Statikbe\FilamentTranslationManager\Http\Livewire;

use Filament\Notifications\Notification;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Statikbe\AiTranslation\AiTranslationService;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationCellEditor extends Component
{
    public string $group;

    public string $translationKey;

    /** @var array<string, string> Locale → current value map */
    public array $translations = [];

    public string $sourceLocale;

    /** @var string[] */
    public array $locales = [];

    public bool $hasAi = false;

    public ?string $aiDriver = null;

    public function mount(
        string $group,
        string $translationKey,
        array $translations,
        string $sourceLocale,
        array $locales,
        bool $hasAi = false,
        ?string $aiDriver = null,
    ): void {
        $this->group = $group;
        $this->translationKey = $translationKey;
        $this->translations = $translations;
        $this->sourceLocale = $sourceLocale;
        $this->locales = $locales;
        $this->hasAi = $hasAi;
        $this->aiDriver = $aiDriver;
    }

    /**
     * Persist all changed locale values for this key in one round-trip.
     * Renderless keeps Alpine's in-memory state intact (no re-render).
     *
     * @param  array<string, string>  $changed  Locale → value map (changed locales only).
     */
    #[Renderless]
    public function save(array $changed): void
    {
        $manager = app(ChainedTranslationManager::class);

        foreach ($changed as $locale => $value) {
            $manager->save($locale, $this->group, $this->translationKey, $value);
        }

        Notification::make()
            ->success()
            ->title(trans('filament-translation-manager::messages.saved_translation'))
            ->send();
    }

    /**
     * Fill missing locales with AI suggestions without saving.
     * Returns a locale → translated-text map for Alpine to merge into its state.
     * Renderless keeps Alpine's in-memory state intact (no re-render).
     *
     * @param  string[]  $locales  Missing locales to translate.
     * @return array<string, string>
     */
    #[Renderless]
    public function aiTranslateMissing(string $sourceText, string $sourceLocale, array $locales): array
    {
        if (blank($sourceText)) {
            Notification::make()
                ->warning()
                ->title(trans('filament-translation-manager::messages.ai_translate_no_source'))
                ->send();

            return [];
        }

        /** @var AiTranslationService $aiService */
        $aiService = app(AiTranslationService::class);
        $results = [];

        foreach ($locales as $locale) {
            $results[$locale] = $aiService->translate($sourceText, $sourceLocale, $locale, driver: $this->aiDriver);
        }

        Notification::make()
            ->success()
            ->title(trans('filament-translation-manager::messages.ai_fill_modal_success'))
            ->send();

        return $results;
    }

    public function render(): \Illuminate\View\View
    {
        return view('filament-translation-manager::livewire.translation-cell-editor');
    }
}
