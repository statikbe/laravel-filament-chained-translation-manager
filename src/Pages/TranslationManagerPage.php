<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
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
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
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
            ->columns($this->buildColumns($plugin, $locales, $sourceLocale, $translatorLocales))
            ->filters($this->buildFilters($plugin))
            ->headerActions($this->buildHeaderActions($plugin, $locales, $sourceLocale))
            ->actions([
                $this->buildEditAction($plugin, $locales, $sourceLocale, $translatorLocales),
                $this->buildAiRowAction($plugin, $locales, $sourceLocale),
            ])
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
            ->striped()
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    // ─── Columns ─────────────────────────────────────────────────────────────

    private function buildColumns(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
        array $translatorLocales,
    ): array {
        $chainedTranslationManager = app(ChainedTranslationManager::class);

        $columns = [
            TextColumn::make('translation_key')
                ->label(trans('filament-translation-manager::messages.key'))
                ->fontFamily(FontFamily::Mono)
                ->color('gray')
                ->copyable()
                ->copyMessage(trans('filament-translation-manager::messages.key_copied'))
                ->sortable()
                ->searchable(),
        ];

        // Source locale — read-only reference column
        $columns[] = TextColumn::make('source_value')
            ->label(strtoupper($sourceLocale))
            ->badge()
            ->color('gray')
            ->getStateUsing(fn(array $record): ?string => $record['translations'][$sourceLocale] ?? null)
            ->placeholder(trans('filament-translation-manager::messages.missing_translation'))
            ->wrap()
            ->limit(100)
            ->searchable(isGlobal: true, isIndividual: false);

        // Editable locale columns
        foreach ($translatorLocales as $locale) {
            $columns[] = TextInputColumn::make('locale_' . $locale)
                ->label(strtoupper($locale))
                ->getStateUsing(fn(array $record) => $record['translations'][$locale] ?? null)
                ->placeholder(trans('filament-translation-manager::messages.missing_translation'))
                ->updateStateUsing(function (?string $state, array $record) use (
                    $locale,
                    $chainedTranslationManager,
                ): void {
                    $chainedTranslationManager->save(
                        $locale,
                        $record['group'],
                        $record['translation_key'],
                        $state ?? '',
                    );

                    Notification::make()
                        ->success()
                        ->title(trans('filament-translation-manager::messages.saved_translation'))
                        ->send();
                });
        }

        return $columns;
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

    private function buildEditAction(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
        array $translatorLocales,
    ): Action {
        $chainedTranslationManager = app(ChainedTranslationManager::class);

        $action = Action::make('edit')
            ->label(trans('filament-translation-manager::messages.edit_action'))
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->fillForm(fn(array $record): array => array_merge([
                '_source' => $record['translations'][$sourceLocale] ?? '',
            ], collect($translatorLocales)->mapWithKeys(fn($locale) => [$locale => $record['translations'][$locale] ?? ''])->all()))
            ->form(function (array $record) use ($sourceLocale, $translatorLocales): array {
                $fields = [
                    TextInput::make('_source')
                        ->label(sprintf(
                            '%s (%s)',
                            strtoupper($sourceLocale),
                            trans('filament-translation-manager::messages.source_locale_label'),
                        ))
                        ->disabled(),
                ];

                foreach ($translatorLocales as $locale) {
                    $fields[] = Textarea::make($locale)
                        ->label(strtoupper($locale))
                        ->rows(3)
                        ->placeholder(trans('filament-translation-manager::messages.missing_translation'));
                }

                return $fields;
            })
            ->action(function (array $record, array $data) use ($translatorLocales, $chainedTranslationManager): void {
                foreach ($translatorLocales as $locale) {
                    if (!array_key_exists($locale, $data)) {
                        continue;
                    }

                    $chainedTranslationManager->save(
                        $locale,
                        $record['group'],
                        $record['translation_key'],
                        $data[$locale] ?? '',
                    );
                }

                Notification::make()
                    ->success()
                    ->title(trans('filament-translation-manager::messages.saved_translation'))
                    ->send();
            })
            ->modalWidth('2xl')
            ->slideOver();

        if ($plugin->hasAiModalAction()) {
            $action->extraModalFooterActions([
                $this->buildAiModalFillAction($plugin, $sourceLocale, $translatorLocales),
            ]);
        }

        return $action;
    }

    private function buildAiRowAction(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
        string $sourceLocale,
    ): Action {
        return Action::make('ai_translate')
            ->label(trans('filament-translation-manager::messages.ai_translate_row_action'))
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->tooltip(trans('filament-translation-manager::messages.ai_translate_row_action_tooltip'))
            ->visible(fn() => $plugin->hasAiRowAction())
            ->action(function (array $record) use ($plugin, $locales, $sourceLocale): void {
                /** @var \Statikbe\AiTranslation\AiTranslationService $aiService */
                $aiService = app(\Statikbe\AiTranslation\AiTranslationService::class);
                $sourceText = $record['translations'][$sourceLocale] ?? '';
                $translatedCount = 0;

                if (blank($sourceText)) {
                    Notification::make()
                        ->warning()
                        ->title(trans('filament-translation-manager::messages.ai_translate_no_source'))
                        ->send();

                    return;
                }

                foreach ($locales as $locale) {
                    if ($locale === $sourceLocale) {
                        continue;
                    }

                    if (!blank($record['translations'][$locale] ?? null)) {
                        continue; // Already translated — skip
                    }

                    $aiService->translateKey(
                        $locale,
                        $record['group'],
                        $record['translation_key'],
                        $sourceText,
                        $plugin->getAiDriver(),
                    );

                    $translatedCount++;
                }

                if ($translatedCount === 0) {
                    Notification::make()
                        ->info()
                        ->title(trans('filament-translation-manager::messages.ai_translate_nothing_missing'))
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(trans('filament-translation-manager::messages.ai_translate_row_success', [
                        'count' => $translatedCount,
                    ]))
                    ->send();

                // Reset the table so that the updates are being detected immediately.
                $this->resetTable();
            });
    }

    private function buildAiModalFillAction(
        FilamentChainedTranslationManagerPlugin $plugin,
        string $sourceLocale,
        array $translatorLocales,
    ): Action {
        return Action::make('ai_fill_modal')
            ->label(trans('filament-translation-manager::messages.ai_fill_modal_action'))
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->action(function (array $record, Set $set) use ($plugin, $sourceLocale, $translatorLocales): void {
                /** @var \Statikbe\AiTranslation\AiTranslationService $aiService */
                $aiService = app(\Statikbe\AiTranslation\AiTranslationService::class);
                $sourceText = $record['translations'][$sourceLocale] ?? '';

                if (blank($sourceText)) {
                    Notification::make()
                        ->warning()
                        ->title(trans('filament-translation-manager::messages.ai_translate_no_source'))
                        ->send();

                    return;
                }

                foreach ($translatorLocales as $locale) {
                    $translated = $aiService->translate(
                        $sourceText,
                        $sourceLocale,
                        $locale,
                        driver: $plugin->getAiDriver(),
                    );

                    $set($locale, $translated);
                }

                Notification::make()
                    ->success()
                    ->title(trans('filament-translation-manager::messages.ai_fill_modal_success'))
                    ->send();
            });
    }

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
                    /** @var \Statikbe\AiTranslation\AiTranslationService $aiService */
                    $aiService = app(\Statikbe\AiTranslation\AiTranslationService::class);
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
        $actions = [];

        if ($plugin->hasAiBulkAction()) {
            $actions[] = BulkAction::make('ai_translate_selected')
                ->label(trans('filament-translation-manager::messages.ai_translate_bulk_action'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Collection $records) use ($plugin, $locales, $sourceLocale): void {
                    /** @var \Statikbe\AiTranslation\AiTranslationService $aiService */
                    $aiService = app(\Statikbe\AiTranslation\AiTranslationService::class);
                    $translatedCount = 0;

                    foreach ($records as $record) {
                        $sourceText = $record['translations'][$sourceLocale] ?? '';

                        if (blank($sourceText)) {
                            continue;
                        }

                        foreach ($locales as $locale) {
                            if ($locale === $sourceLocale) {
                                continue;
                            }

                            if (!blank($record['translations'][$locale] ?? null)) {
                                continue;
                            }

                            $aiService->translateKey(
                                $locale,
                                $record['group'],
                                $record['translation_key'],
                                $sourceText,
                                $plugin->getAiDriver(),
                            );

                            $translatedCount++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title(trans('filament-translation-manager::messages.ai_translate_bulk_success', [
                            'count' => $translatedCount,
                        ]))
                        ->send();
                })
                ->deselectRecordsAfterCompletion();
        }

        return $actions;
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
            $records = $records->filter(function (array $record) use ($search): bool {
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

        if (!empty($selectedGroups)) {
            $records = $records->filter(fn(array $record) => in_array($record['group'], $selectedGroups, true));
        }

        if ($missingOnly) {
            $records = $records->filter(fn(array $record) => $this->hasMissingTranslations($record, $locales));
        }

        $records = $records->sortBy([
            ['group',           'asc'],
            ['translation_key', 'asc'],
        ])->values();

        $total = $records->count();
        $items = $records->forPage($page, $recordsPerPage)->values()->all();

        return new LengthAwarePaginator(
            $items,
            $total,
            $recordsPerPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    private function getAllTranslationRecords(
        FilamentChainedTranslationManagerPlugin $plugin,
        array $locales,
    ): Collection {
        $chainedTranslationManager = app(ChainedTranslationManager::class);
        $ignoreGroups = $plugin->getIgnoreGroups();
        $groups = collect($chainedTranslationManager->getTranslationGroups())->diff($ignoreGroups)->values()->all();

        $data = [];

        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                $translations = $chainedTranslationManager->getTranslationsForGroup($locale, $group);

                foreach ($translations as $key => $value) {
                    $recordKey = $group . '.' . $key;

                    if (!array_key_exists($recordKey, $data)) {
                        $data[$recordKey] = [
                            '__key' => $recordKey,
                            'group' => $group,
                            'translation_key' => $key,
                            'translations' => [],
                        ];
                    }

                    $data[$recordKey]['translations'][$locale] = $value;
                }
            }
        }

        return collect(array_values($data));
    }

    private function hasMissingTranslations(array $record, array $locales): bool
    {
        foreach ($locales as $locale) {
            $value = $record['translations'][$locale] ?? null;

            if (blank($value)) {
                return true;
            }
        }

        return false;
    }

    private function getTranslationGroups(FilamentChainedTranslationManagerPlugin $plugin): array
    {
        $chainedTranslationManager = app(ChainedTranslationManager::class);

        return collect($chainedTranslationManager->getTranslationGroups())
            ->diff($plugin->getIgnoreGroups())
            ->values()
            ->all();
    }
}

