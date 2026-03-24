<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Concerns;

use Statikbe\FilamentTranslationManager\FilamentTranslationManager;

trait HasLocaleConfiguration
{
    protected array $locales = [];

    protected ?string $sourceLocale = null;

    protected array $ignoreGroups = [];

    public function locales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function sourceLocale(string $locale): static
    {
        $this->sourceLocale = $locale;

        return $this;
    }

    public function ignoreGroups(array $groups): static
    {
        $this->ignoreGroups = $groups;

        return $this;
    }

    public function getLocales(): array
    {
        if ($this->locales !== []) {
            return $this->locales;
        }

        return FilamentTranslationManager::getLocales();
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale ?? config('app.locale', 'en');
    }

    public function getIgnoreGroups(): array
    {
        return $this->ignoreGroups;
    }
}
