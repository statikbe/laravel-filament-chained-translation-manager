<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Statikbe\AiTranslation\AiTranslationService;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationRecordService
{
    public function __construct(
        private readonly FilamentChainedTranslationManagerPlugin $plugin,
        private readonly TranslationCollectorService $collector,
        private readonly TranslationFilterService $filter,
    ) {}

    public function buildRecords(?array $filters, ?string $search, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $locales = $this->plugin->getLocales();
        $sourceLocale = $this->plugin->getSourceLocale();
        $selectedGroups = $filters['group']['values'] ?? [];
        $missingOnly = $filters['missing']['isActive'] ?? false;

        $selectedLocales = $filters['locales']['values'] ?? [];
        $displayLocales = $selectedLocales !== []
            ? array_values(array_filter($locales, static fn($l) => in_array($l, $selectedLocales, true)))
            : $locales;

        $records = $this->getAllTranslationRecords($locales);

        if (filled($search)) {
            $records = $this->filter->filterBySearch($records, $search);
        }

        if ($selectedGroups !== []) {
            $records = $this->filter->filterByGroups($records, $selectedGroups);
        }

        if ($missingOnly) {
            $records = $this->filter->filterByMissing($records, $displayLocales);
        }

        $records = $records->sortBy([
            ['group',           'asc'],
            ['translation_key', 'asc'],
        ])->values();

        return new LengthAwarePaginator(
            $records
                ->forPage($page, $recordsPerPage)
                ->values()
                ->map(
                    static fn(array $r) => $r
                    + ['display_locales' => $displayLocales, 'source_locale' => $sourceLocale],
                )
                ->all(),
            $records->count(),
            $recordsPerPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    public function getAllTranslationRecords(array $locales): Collection
    {
        $groups = $this->getTranslationGroups();

        return $this->collector->collectAllTranslations($locales, $groups);
    }

    public function getTranslationGroups(): array
    {
        return collect(app(ChainedTranslationManager::class)->getTranslationGroups())
            ->diff($this->plugin->getIgnoreGroups())
            ->values()
            ->all();
    }

    public function aiTranslateMissingLocales(
        array $record,
        array $locales,
        string $sourceLocale,
        AiTranslationService $aiService,
    ): int {
        $count = 0;
        $sourceText = $record['translations'][$sourceLocale] ?? '';

        if (!method_exists($aiService, 'translateKey')) {
            return 0;
        }

        foreach ($locales as $locale) {
            if ($locale === $sourceLocale || !blank($record['translations'][$locale] ?? null)) {
                continue;
            }

            $aiService->translateKey(
                $locale,
                $record['group'],
                $record['translation_key'],
                $sourceText,
                $this->plugin->getAiDriver(),
            );

            $count++;
        }

        return $count;
    }
}
