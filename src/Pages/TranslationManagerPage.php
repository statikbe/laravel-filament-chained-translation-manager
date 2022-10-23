<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationManagerPage extends Page
{
    const PAGE_LIMIT = 20;

    protected static ?string $navigationIcon = 'heroicon-o-translate';

    private ChainedTranslationManager $chainedTranslationManager;

    /**
     * @var array<string>
     */
    public array $groups;

    /**
     * @var array<string>
     */
    public array $locales;

    /**
     * @var Collection{ 'title': string, 'type': string, 'group': string, 'key': string, 'translations': array<string, string> }
     */
    public Collection $filteredTranslations;

    //filters
    public string $searchTerm = '';

    public bool $onlyShowMissingTranslations = false;

    public array $selectedGroups = [];

    public array $selectedLocales = [];

    public int $pageCounter = 1;

    public int $pagedTranslations = 0;

    public int $totalFilteredTranslations = 0;

    public int $totalTranslations = 0;

    public int $totalMissingFilteredTranslations = 0;

    protected $queryString = [
        'pageCounter' => [
            'except' => 1,
            'as' => 'page',
        ],
        'searchTerm' => [
            'as' => 'search',
            'except' => '',
        ],
        'onlyShowMissingTranslations' => [
            'except' => true,
            'as' => 'showMissing',
        ],
        'selectedGroups',
        'selectedLocales',
    ];

    protected static string $view = 'filament-translation-manager::pages.translation-manager-page';

    protected static function shouldRegisterNavigation(): bool
    {
        if (config('filament-translation-manager.access.limited')) {
            return Gate::allows(config('filament-translation-manager.access.gate'));
        }

        return true;
    }

    protected static function getNavigationGroup(): ?string
    {
        return trans('filament-translation-manager::messages.navigation-group');
    }

    protected static function getNavigationLabel(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    protected function getTitle(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public function mount(): void
    {
        if (config('filament-translation-manager.access.limited')) {
            Gate::authorize(config('filament-translation-manager.access.gate'));
        }

        $this->loadInitialData();
    }

    private function loadInitialData(): void
    {
        $groups = $this->getChainedTranslationManager()->getTranslationGroups();
        $this->groups = collect($groups)->diff(config('filament-translation-manager.ignore_groups', []))->values()->toArray();

        $this->locales = $this->getLocalesData();
        $this->selectedLocales = $this->locales;

        $this->filterTranslations();
    }

    /**
     * Returns a list of the configured, supported locales (key: locale) with their names (key: language).
     *
     * @return array
     */
    protected function getLocalesData(): array
    {
        return FilamentTranslationManager::getLocales();
    }

    private function getTranslations(): array
    {
        $data = [];

        foreach ($this->locales as $locale) {
            foreach ($this->groups as $group) {
                $this->addTranslationsToData($data, $locale, $group);
            }
        }

        return array_values($data);
    }

    private function addTranslationsToData(array &$data, string $locale, string $group): array
    {
        $translations = $this->getChainedTranslationManager()->getTranslationsForGroup($locale, $group);

        //transform to data structure necessary for frontend
        foreach ($translations as $key => $translation) {
            $dataKey = $group.'.'.$key;
            if (! array_key_exists($dataKey, $data)) {
                $data[$dataKey] = [
                    'title' => $group.' - '.$key,
                    'type' => 'group',
                    'group' => $group,
                    'translation-key' => $key,
                    'translations' => [],
                ];
            }
            $data[$dataKey]['translations'][$locale] = $translation;
        }

        return $data;
    }

    public function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(12)
                ->schema([
                    TextInput::make('searchTerm')
                        ->disableLabel()
                        ->placeholder(trans('filament-translation-manager::messages.search_term_placeholder'))
                        ->prefixIcon('heroicon-o-search')
                        ->columnSpan(3),
                    Checkbox::make('onlyShowMissingTranslations')
                        ->label(trans('filament-translation-manager::messages.only_show_missing_translations_lbl'))
                        ->default(false)
                        ->columnSpan(3),
                    Select::make('selectedGroups')
                        ->disableLabel()
                        ->placeholder(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                        ->multiple()
                        ->options(array_combine($this->groups, $this->groups))
                        ->columnSpan(3),
                    Select::make('selectedLocales')
                        ->disableLabel()
                        ->placeholder(trans('filament-translation-manager::messages.selected_languages_placeholder'))
                        ->multiple()
                        ->options(array_combine($this->locales, $this->locales))
                        ->columnSpan(3),
                ]),
        ];
    }

    public function filterTranslations(): void
    {
        $filteredTranslations = collect($this->getTranslations());
        $this->totalTranslations = $filteredTranslations->count();

        if ($this->searchTerm) {
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) {
                foreach ($translationItem['translations'] as $translation) {
                    if (Str::contains($translation, $this->searchTerm, true)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($this->onlyShowMissingTranslations) {
            $selectedLocales = ! empty($this->selectedLocales) ? $this->selectedLocales : $this->locales;
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) use ($selectedLocales) {
                foreach ($translationItem['translations'] as $locale => $translation) {
                    if (in_array($locale, $selectedLocales)) {
                        if (empty($translation) || trim($translation) === '') {
                            return true;
                        }
                    }
                }

                return false;
            });
        }

        if (! empty($this->selectedGroups)) {
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) {
                return in_array($translationItem['group'], $this->selectedGroups);
            });
        }

        $this->totalMissingFilteredTranslations = $this->countMissingTranslations($filteredTranslations);

        $filteredTranslations = $this->paginateTranslations($filteredTranslations);

        $this->filteredTranslations = $filteredTranslations;
    }

    private function paginateTranslations(Collection $translations): Collection
    {
        $translations = $translations->sortBy([
            ['group', 'asc'],
            ['key', 'asc'],
        ]);

        $offset = 0;
        if ($this->pageCounter > 1) {
            $offset = ($this->pageCounter - 1) * self::PAGE_LIMIT;
        }

        $this->pagedTranslations = $offset + self::PAGE_LIMIT;
        $this->totalFilteredTranslations = count($translations);

        return $translations->slice($offset, self::PAGE_LIMIT);
    }

    private function getChainedTranslationManager(): ChainedTranslationManager
    {
        if (! isset($this->chainedTranslationManager)) {
            $this->chainedTranslationManager = app(ChainedTranslationManager::class);
        }

        return $this->chainedTranslationManager;
    }

    public function submitFilters(): void
    {
        $this->pageCounter = 1;
        $this->filterTranslations();
    }

    public function previousPage(): void
    {
        if ($this->pageCounter > 1) {
            $this->pageCounter -= 1;
            $this->filterTranslations();
        }
    }

    public function nextPage(): void
    {
        if ($this->pageCounter * self::PAGE_LIMIT <= $this->totalFilteredTranslations) {
            $this->pageCounter += 1;
            $this->filterTranslations();
        }
    }

    private function countMissingTranslations(Collection $translations): int
    {
        $selectedLocales = ! empty($this->selectedLocales) ? $this->selectedLocales : $this->locales;

        return $translations->reduce(function ($carry, $translationItem) use ($selectedLocales) {
            $missing = false;
            //check if all selected locales are available in the translation item, by intersecting the locales of the
            // translation item and the selected locales and seeing if the size matches with the selected locales.
            if (count(array_intersect($selectedLocales, array_keys($translationItem['translations']))) !== count($selectedLocales)) {
                $missing = true;
            } else {
                foreach ($translationItem['translations'] as $locale => $translation) {
                    if (in_array($locale, $selectedLocales)) {
                        if (empty($translation) || trim($translation) === '') {
                            $missing = true;
                        }
                    }
                }
            }

            return $carry + ($missing ? 1 : 0);
        }, 0);
    }
}
