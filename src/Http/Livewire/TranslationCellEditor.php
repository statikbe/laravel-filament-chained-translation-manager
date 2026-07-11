<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Http\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Statikbe\AiTranslation\AiTranslationService;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
use Statikbe\FilamentTranslationManager\Support\TranslationCollectorService;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationCellEditor extends Component
{
    #[Locked]
    public string $group;

    #[Locked]
    public string $translationKey;

    /** @var array<string, string> Locale → current value map */
    public array $translations = [];

    #[Locked]
    public string $sourceLocale;

    /** @var string[] */
    #[Locked]
    public array $locales = [];

    #[Locked]
    public bool $hasAi = false;

    #[Locked]
    public ?string $aiDriver = null;

    /**
     * @param  array{translations: array<string, string>, locales: string[], source_locale: string}  $content
     * @param  array{enabled?: bool, driver?: string|null}  $ai
     */
    public function mount(string $group, string $translationKey, array $content, array $ai = []): void
    {
        $this->authorizeGate();

        $this->group = $group;
        $this->translationKey = $translationKey;
        $this->translations = $content['translations'] ?? [];
        $this->sourceLocale = $content['source_locale'];
        $this->locales = $content['locales'];
        $this->hasAi = $ai['enabled'] ?? false;
        $this->aiDriver = $ai['driver'] ?? null;
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
        $this->authorizeGate();

        $changed = array_intersect_key($changed, array_flip($this->locales));

        $manager = app(ChainedTranslationManager::class);

        foreach ($changed as $locale => $value) {
            $manager->save($locale, $this->group, $this->translationKey, $value);
        }

        TranslationCollectorService::flushCache();

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
        $this->authorizeGate();

        if (blank($sourceText)) {
            Notification::make()
                ->warning()
                ->title(trans('filament-translation-manager::messages.ai_translate_no_source'))
                ->send();

            return [];
        }

        $locales = array_values(array_intersect($locales, $this->locales));

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

    private function authorizeGate(): void
    {
        $gate = FilamentChainedTranslationManagerPlugin::get()->getGate();

        if ($gate) {
            Gate::authorize($gate);
        }
    }

    public function render(): View
    {
        return view('filament-translation-manager::livewire.translation-cell-editor');
    }
}
