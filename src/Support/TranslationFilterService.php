<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TranslationFilterService
{
    public function filterBySearch(Collection $records, string $search): Collection
    {
        return $records->filter(fn (array $record) => $this->recordMatchesSearch($record, $search));
    }

    public function filterByGroups(Collection $records, array $selectedGroups): Collection
    {
        return $records->filter(static fn (array $record) => in_array($record['group'], $selectedGroups, true));
    }

    public function filterByMissing(Collection $records, array $locales): Collection
    {
        return $records->filter(fn (array $record) => $this->hasMissingTranslations($record, $locales));
    }

    private function recordMatchesSearch(array $record, string $search): bool
    {
        if (Str::contains($record['translation_key'], $search, ignoreCase: true)) {
            return true;
        }

        if (Str::contains($record['group'], $search, ignoreCase: true)) {
            return true;
        }

        return $this->translationsContainSearch($record['translations'], $search);
    }

    private function translationsContainSearch(array $translations, string $search): bool
    {
        foreach ($translations as $value) {
            if (Str::contains((string) ($value ?? ''), $search, ignoreCase: true)) {
                return true;
            }
        }

        return false;
    }

    private function hasMissingTranslations(array $record, array $locales): bool
    {
        foreach ($locales as $locale) {
            if (blank($record['translations'][$locale] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
