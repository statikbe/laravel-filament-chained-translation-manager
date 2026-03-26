<?php

declare(strict_types=1);

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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Statikbe\AiTranslation\AiTranslationService;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
use Statikbe\FilamentTranslationManager\Support\TranslationCollectorService;
use Statikbe\FilamentTranslationManager\Support\TranslationFilterService;
use Statikbe\FilamentTranslationManager\Support\TranslationRecordService;
use Statikbe\FilamentTranslationManager\Tables\Columns\TranslationCellColumn;

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
        $service = new TranslationRecordService(
            $plugin,
            new TranslationCollectorService(),
            new TranslationFilterService(),
        );

        $groups = $service->getTranslationGroups();

        return $table
            ->records(
                static fn(
                    ?array $filters,
                    ?string $search,
                    int|string $page,
                    int|string $recordsPerPage,
                ): LengthAwarePaginator => $service->buildRecords(
                    $filters,
                    $search,
                    (int) $page,
                    (int) $recordsPerPage,
                ),
            )
            ->columns([
                TranslationCellColumn::make('translations')
                    ->label('')
                    ->searchable()
                    ->getStateUsing(static fn(array $record) => [
                        'group' => $record['group'],
                        'translation_key' => $record['translation_key'],
                        'translations' => $record['translations'] ?? [],
                        'locales' => $record['display_locales'],
                        'source_locale' => $record['source_locale'],
                        'has_ai' => $plugin->hasAiRowAction(),
                        'ai_driver' => $plugin->getAiDriver(),
                    ])
                    ->grow(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                    ->options(array_combine($groups, $groups))
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('locales')
                    ->label(trans('filament-translation-manager::messages.selected_languages_placeholder'))
                    ->options(array_combine($locales, $locales))
                    ->multiple()
                    ->searchable(),

                Filter::make('missing')
                    ->label(trans('filament-translation-manager::messages.only_show_missing_translations_lbl'))
                    ->toggle(),
            ])
            ->headerActions($this->buildHeaderActions($plugin, $locales, $sourceLocale))
            ->actions([])
            ->bulkActions([
                BulkActionGroup::make($this->buildBulkActions($plugin, $locales, $sourceLocale)),
            ])
            ->searchPlaceholder(trans('filament-translation-manager::messages.search_term_placeholder'))
            ->emptyStateHeading(trans('filament-translation-manager::messages.error_no_translations_for_filters'))
            ->emptyStateDescription(trans(
                'filament-translation-manager::messages.error_no_translations_for_filters_description',
            ))
            ->emptyStateIcon('heroicon-o-language')
            ->persistFiltersInSession()
            ->persistSearchInSession();
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
                ->action(static function () use ($plugin, $locales, $sourceLocale): void {
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

        $service = new TranslationRecordService(
            $plugin,
            new TranslationCollectorService(),
            new TranslationFilterService(),
        );

        return [
            BulkAction::make('ai_translate_selected')
                ->label(trans('filament-translation-manager::messages.ai_translate_bulk_action'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->action(static function (Collection $records) use ($locales, $sourceLocale, $service): void {
                    /** @var AiTranslationService $aiService */
                    $aiService = app(AiTranslationService::class);
                    $translatedCount = 0;

                    foreach ($records as $record) {
                        if (blank($record['translations'][$sourceLocale] ?? '')) {
                            continue;
                        }

                        $translatedCount += $service->aiTranslateMissingLocales(
                            $record,
                            $locales,
                            $sourceLocale,
                            $aiService,
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
}
