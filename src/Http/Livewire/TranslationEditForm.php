<?php

namespace Statikbe\FilamentTranslationManager\Http\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;
use Statikbe\LaravelChainedTranslator\Exceptions\InvalidTranslationPathException;

class TranslationEditForm extends Component
{
    #[Locked]
    public string $group = '';

    #[Locked]
    public string $translationKey = '';

    public array $translations = [];

    #[Locked]
    public array $initialTranslations = [];

    #[Locked]
    public array $locales = [];

    /**
     * @const string
     */
    const EVENT_TRANSLATIONS_SAVED = 'translationsSaved';

    public function mount(): void
    {
        $this->locales = FilamentTranslationManager::filterAllowedValues($this->locales, FilamentTranslationManager::getLocales());
        $this->initialTranslations = $this->translations;
    }

    /**
     * Only accept strings (or null for missing translations) from the client, otherwise restore the initial value.
     */
    public function updatedTranslations(): void
    {
        foreach ($this->translations as $locale => $translation) {
            if ($translation !== null && ! is_string($translation)) {
                $this->translations[$locale] = $this->initialTranslations[$locale] ?? null;
            }
        }
    }

    public function save(string $locale): void
    {
        FilamentTranslationManager::authorize();

        if (! in_array($locale, $this->locales, true)
            || ! FilamentTranslationManager::isAllowedLocale($locale)
            || ! FilamentTranslationManager::isAllowedGroup($this->group)) {
            abort(422);
        }

        $translation = $this->translations[$locale] ?? null;

        if ($translation === ($this->initialTranslations[$locale] ?? null)) {
            return;
        }

        if (! is_string($translation)) {
            abort(422);
        }

        try {
            app(ChainedTranslationManager::class)->save(
                $locale,
                $this->group,
                $this->translationKey,
                $translation
            );
        } catch (InvalidTranslationPathException) {
            abort(422);
        }

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
