<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Statikbe\AiTranslation\AiTranslationService;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
use Statikbe\FilamentTranslationManager\Tables\Columns\TranslationCellColumn;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationManagerPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-translation-manager::pages.translation-manager-page';

    // ─── Navigation ──────────────────────────────────────────────────────────

    public static function shouldRegisterNavigation(): bool
    {
        $gate = FilamentChainedTranslationManagerPlugin::get()->getGate();

        return $gate ? Gate::allows($gate) : true;
    }

    public static function getNavigationLabel(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentChainedTranslationManagerPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return FilamentChainedTranslationManagerPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentChainedTranslationManagerPlugin::get()->getNavigationSort();
    }

    public function getTitle(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public function mount(): void
    {
        $gate = FilamentChainedTranslationManagerPlugin::get()->getGate();

        if ($gate) {
            Gate::authorize($gate);
        }
    }

    // ─── Public Livewire methods ──────────────────────────────────────────────

    /**
     * Called by the inline translation editor (Alpine.js → $wire) to persist
     * all changed locale values for a single key in one round-trip.
     *
     * @param  array<string, string>  $translations  Locale → value map (changed locales only).
     */
    /**
     * Fill translation fields with AI suggestions without saving.
     * Returns a locale → translated-text map so Alpine can populate the form
     * for user review before they commit with Save.
     *
     * @param  string[]  $locales  Translator locales to fill (missing ones only).
     * @return array<string, string>
     */
    public function aiTranslateMissingInline(
        string $sourceText,
        string $sourceLocale,
        array $locales,
        ?string $driver,
    ): array {
        if (blank($sourceText)) {
            $this->sendAiNoSourceWarning();

            return [];
        }

        /** @var AiTranslationService $aiService */
        $aiService = app(AiTranslationService::class);
        $results = [];

        foreach ($locales as $locale) {
            $results[$locale] = $aiService->translate($sourceText, $sourceLocale, $locale, driver: $driver);
        }

        Notification::make()
            ->success()
            ->title(trans('filament-translation-manager::messages.ai_fill_modal_success'))
            ->send();

        return $results;
    }

    public function saveInlineTranslation(string $group, string $key, array $translations): void
    {
        $manager = app(ChainedTranslationManager::class);

        foreach ($translations as $locale => $value) {
            $manager->save($locale, $group, $key, $value);
        }

        $this->sendSavedNotification();
    }

    // ─── Table ───────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        $plugin = FilamentChainedTranslationManagerPlugin::get();
        $locales = $plugin->getLocales();
        $sourceLocale = $plugin->getSourceLocale();
        $translatorLocales = array_values(array_filter($locales, fn($l) => $l !== $sourceLocale));

        return $table
            ->records(function (?array $filters, ?string $search, int|string $page, int|string $recordsPerPage) use (
                $plugin,
                $locales,
                $sourceLocale,
            ): LengthAwarePaginator {
                return $this->buildRecords(
                    $plugin,
                    $locales,
                    $sourceLocale,
                    $filters,
                    $search,
                    (int) $page,
                    (int) $recordsPerPage,
                );
            })
            ->columns($this->buildColumns($plugin, $sourceLocale, $translatorLocales))
            ->filters($this->buildFilters($plugin))
            ->headerActions($this->buildHeaderActions($plugin, $locales, $sourceLocale))
            ->actions([])
            ->bulkActions([
                BulkActionGroup::make($this->buildBulkActions($plugin, $locales, $sourceLocale)),
            ])
            ->groups([
                Group::make('group')->label(trans('filament-translation-manager::messages.group'))->collapsible(),
            ])
            ->defaultGroup('group')
            ->defaultSort('translation_key')
            ->searchPlaceholder(trans('filament-translation-manager::messages.search_term_placeholder'))
            ->emptyStateHeading(trans('filament-translation-manager::messages.error_no_translations_for_filters'))
            ->emptyStateDescription(trans(
                'filament-translation-manager::messages.error_no_translations_for_filters_description',
            ))
            ->emptyStateIcon('heroicon-o-language')
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    // ─── Columns ─────────────────────────────────────────────────────────────

    private function buildColumns(
        FilamentChainedTranslationManagerPlugin $plugin,
        string $sourceLocale,
        array $translatorLocales,
    ): array {
        return [
            TranslationCellColumn::make('translations')
                ->label('')
                ->searchable()
                ->getStateUsing(fn(array $record) => [
                    'group' => $record['group'],
                    'translation_key' => $record['translation_key'],
                    'translations' => $record['translations'] ?? [],
                    // Source locale listed first so it appears at the top of the editor.
                    'locales' => array_merge([$sourceLocale], $translatorLocales),
                    'source_locale' => $sourceLocale,
                    'has_ai' => $plugin->hasAiRowAction(),
                    'ai_driver' => $plugin->getAiDriver(),
                ])
                ->grow(),
        ];
    }

    // ─── Filters ─────────────────────────────────────────────────────────────

    private function buildFilters(FilamentChainedTranslationManagerPlugin $plugin): array
    {
        $groups = $this->getTranslationGroups($plugin);

        return [
            SelectFilter::make('group')
                ->label(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                ->options(array_combine($groups, $groups))
                ->multiple()
                ->searchable(),

            Filter::make('missing')
                ->label(trans('filament-translation-manager::messages.only_show_missing_translations_lbl'))
                ->toggle(),
        ];
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    /** @return Action[] */
    private function buildHeaderActions(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
    ): array {
        if (!$plugin->hasAiHeaderAction()) {
            return [];
        }

        return [
            Action::make('ai_translate_all_missing')
                ->label(trans('filament-translation-manager::messages.ai_translate_all_missing_action'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(trans('filament-translation-manager::messages.ai_translate_all_missing_heading'))
                ->modalDescription(trans('filament-translation-manager::messages.ai_translate_all_missing_description'))
                ->modalSubmitActionLabel(trans(
                    'filament-translation-manager::messages.ai_translate_all_missing_confirm',
                ))
                ->action(function () use ($plugin, $locales, $sourceLocale): void {
                    /** @var AiTranslationService $aiService */
                    $aiService = app(AiTranslationService::class);
                    $queuedLocales = 0;

                    foreach ($locales as $locale) {
                        if ($locale === $sourceLocale) {
                            continue;
                        }

                        $aiService->queueMissingForLocale($locale, driver: $plugin->getAiDriver());
                        $queuedLocales++;
                    }

                    Notification::make()
                        ->success()
                        ->title(trans('filament-translation-manager::messages.ai_translate_all_missing_queued', [
                            'count' => $queuedLocales,
                        ]))
                        ->send();
                }),
        ];
    }

    /** @return BulkAction[] */
    private function buildBulkActions(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
    ): array {
        if (!$plugin->hasAiBulkAction()) {
            return [];
        }

        return [
            BulkAction::make('ai_translate_selected')
                ->label(trans('filament-translation-manager::messages.ai_translate_bulk_action'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Collection $records) use ($plugin, $locales, $sourceLocale): void {
                    /** @var AiTranslationService $aiService */
                    $aiService = app(AiTranslationService::class);
                    $translatedCount = 0;

                    foreach ($records as $record) {
                        if (blank($record['translations'][$sourceLocale] ?? '')) {
                            continue;
                        }

                        $translatedCount += $this->aiTranslateMissingLocales(
                            $record,
                            $locales,
                            $sourceLocale,
                            $aiService,
                            $plugin->getAiDriver(),
                        );
                    }

                    Notification::make()
                        ->success()
                        ->title(trans('filament-translation-manager::messages.ai_translate_bulk_success', [
                            'count' => $translatedCount,
                        ]))
                        ->send();
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    // ─── Data ────────────────────────────────────────────────────────────────

    private function buildRecords(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
        ?array $filters,
        ?string $search,
        int $page,
        int $recordsPerPage,
    ): LengthAwarePaginator {
        $selectedGroups = $filters['group']['values'] ?? [];
        $missingOnly = $filters['missing']['isActive'] ?? false;

        $records = $this->getAllTranslationRecords($plugin, $locales);

        if (filled($search)) {
            $records = $this->filterRecordsBySearch($records, $search);
        }

        if (!empty($selectedGroups)) {
            $records = $this->filterRecordsByGroups($records, $selectedGroups);
        }

        if ($missingOnly) {
            $records = $this->filterRecordsByMissing($records, $locales);
        }

        $records = $records->sortBy([
            ['group',           'asc'],
            ['translation_key', 'asc'],
        ])->values();

        return new LengthAwarePaginator(
            $records->forPage($page, $recordsPerPage)->values()->all(),
            $records->count(),
            $recordsPerPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    private function getAllTranslationRecords(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
    ): Collection {
        $manager = app(ChainedTranslationManager::class);
        $groups = $this->getTranslationGroups($plugin);
        $data = [];

        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                foreach ($manager->getTranslationsForGroup($locale, $group) as $key => $value) {
                    $recordKey = $group . '.' . $key;

                    $data[$recordKey] ??= [
                        '__key' => $recordKey,
                        'group' => $group,
                        'translation_key' => $key,
                        'translations' => [],
                    ];

                    $data[$recordKey]['translations'][$locale] = $value;
                }
            }
        }

        return collect(array_values($data));
    }

    private function getTranslationGroups(FilamentChainedTranslationManagerPlugin $plugin): array
    {
        return collect(app(ChainedTranslationManager::class)->getTranslationGroups())
            ->diff($plugin->getIgnoreGroups())
            ->values()
            ->all();
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

    // ─── Filter helpers ───────────────────────────────────────────────────────

    private function filterRecordsBySearch(Collection $records, string $search): Collection
    {
        return $records->filter(function (array $record) use ($search): bool {
            if (Str::contains($record['translation_key'], $search, ignoreCase: true)) {
                return true;
            }

            if (Str::contains($record['group'], $search, ignoreCase: true)) {
                return true;
            }

            foreach ($record['translations'] as $value) {
                if (Str::contains((string) ($value ?? ''), $search, ignoreCase: true)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function filterRecordsByGroups(Collection $records, array $selectedGroups): Collection
    {
        return $records->filter(fn(array $record) => in_array($record['group'], $selectedGroups, true));
    }

    private function filterRecordsByMissing(Collection $records, array $locales): Collection
    {
        return $records->filter(fn(array $record) => $this->hasMissingTranslations($record, $locales));
    }

    // ─── Action helpers ───────────────────────────────────────────────────────

    /**
     * Translate all missing locales for a single record.
     * Returns the number of locales that were queued/translated.
     */
    private function aiTranslateMissingLocales(
        array $record,
        array $locales,
        string $sourceLocale,
        AiTranslationService $aiService,
        ?string $driver,
    ): int {
        $count = 0;
        $sourceText = $record['translations'][$sourceLocale] ?? '';

        foreach ($locales as $locale) {
            if ($locale === $sourceLocale || !blank($record['translations'][$locale] ?? null)) {
                continue;
            }

            $aiService->translateKey($locale, $record['group'], $record['translation_key'], $sourceText, $driver);

            $count++;
        }

        return $count;
    }

    // ─── Notification helpers ─────────────────────────────────────────────────

    private function sendSavedNotification(): void
    {
        Notification::make()
            ->success()
            ->title(trans('filament-translation-manager::messages.saved_translation'))
            ->send();
    }

    private function sendAiNoSourceWarning(): void
    {
        Notification::make()
            ->warning()
            ->title(trans('filament-translation-manager::messages.ai_translate_no_source'))
            ->send();
    }
}

