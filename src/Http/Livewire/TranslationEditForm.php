<?php

namespace Statikbe\FilamentTranslationManager\Http\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationEditForm extends Component
{
    public string $group;

    public string $translationKey;

    public array $translations;

    public array $initialTranslations;

    public array $locales;

    /**
     * @const string
     */
    const EVENT_TRANSLATIONS_SAVED = 'translationsSaved';

    public function mount(): void
    {
        $this->initialTranslations = $this->translations;
    }

    public function save(string $locale): void
    {
        $chainedTranslationManager = app(ChainedTranslationManager::class);

        if (($this->translations[$locale] ?? null) === ($this->initialTranslations[$locale] ?? null)) {
            return;
        }

        $chainedTranslationManager->save(
            $locale,
            $this->group,
            $this->translationKey,
            $this->translations[$locale]
        );

        $this->dispatch(self::EVENT_TRANSLATIONS_SAVED, $this->group, $this->translationKey, $this->translations, $this->initialTranslations);

        $this->initialTranslations = $this->translations;

        Notification::make()
            ->success()
            ->title(trans('filament-translation-manager::messages.saved_translation'))
            ->send();
    }

    public function cancel(): void
    {
        $this->translations = $this->initialTranslations;
    }

    public function render(): View
    {
        return view('filament-translation-manager::livewire.translation-edit-form');
    }
}
