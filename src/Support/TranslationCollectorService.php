<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Support;

use Illuminate\Support\Collection;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationCollectorService
{
    public function collectAllTranslations(array $locales, array $groups): Collection
    {
        $manager = app(ChainedTranslationManager::class);
        $data = [];

        foreach ($locales as $locale) {
            $data = $this->collectForLocale($manager, $locale, $groups, $data);
        }

        return collect(array_values($data));
    }

    private function collectForLocale(
        ChainedTranslationManager $manager,
        string $locale,
        array $groups,
        array $data,
    ): array {
        foreach ($groups as $group) {
            $data = $this->collectForGroup($manager, $locale, $group, $data);
        }

        return $data;
    }

    private function collectForGroup(
        ChainedTranslationManager $manager,
        string $locale,
        string $group,
        array $data,
    ): array {
        foreach ($manager->getTranslationsForGroup($locale, $group) as $key => $value) {
            $data = $this->addTranslation($data, $group, (string) $key, $locale, $value);
        }

        return $data;
    }

    private function addTranslation(array $data, string $group, string $key, string $locale, mixed $value): array
    {
        $recordKey = $group . '.' . $key;

        $data[$recordKey] ??= [
            '__key' => $recordKey,
            'group' => $group,
            'translation_key' => $key,
            'translations' => [],
        ];

        $data[$recordKey]['translations'][$locale] = $value;

        return $data;
    }
}
