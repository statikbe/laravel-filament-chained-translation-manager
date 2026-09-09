<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\FilamentTranslationManager\Http\Livewire\TranslationEditForm;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationManagerPage extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * @const int
     */
    const PAGE_LIMIT = 20;

    private ChainedTranslationManager $chainedTranslationManager;

    public array $groups;

    public array $locales;

    public Collection $filteredTranslations;

    public string $searchTerm = '';

    public bool $onlyShowMissingTranslations = false;

    public array $selectedGroups = [];

    public array $selectedLocales = [];

    public int $pageCounter = 1;

    public int $pagedTranslations = 0;

    public int $totalFilteredTranslations = 0;

    public int $totalTranslations = 0;

    public int $totalMissingFilteredTranslations = 0;

    protected array $queryString = [
        'pageCounter' => [
            'except' => 1,
            'as' => 'page',
        ],
        'searchTerm' => [
            'as' => 'search',
            'except' => '',
        ],
        'onlyShowMissingTranslations' => [
            'except' => false,
            'as' => 'showMissing',
        ],
        'selectedGroups',
        'selectedLocales',
    ];

    protected $listeners = [TranslationEditForm::EVENT_TRANSLATIONS_SAVED => 'translationsSaved'];

    protected string $view = 'filament-translation-manager::pages.translation-manager-page';

    public static function shouldRegisterNavigation(): bool
    {
        if (config('filament-translation-manager.gate', config('filament-translation-manager.access.gate'))) {
            return Gate::allows(config('filament-translation-manager.gate', config('filament-translation-manager.access.gate')));
        }

        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return trans(config('filament-translation-manager.navigation_group'));
    }

    public static function getNavigationLabel(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('filament-translation-manager.navigation_icon') ?? null;
    }

    public function getTitle(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public function mount(): void
    {
        if (config('filament-translation-manager.gate', config('filament-translation-manager.access.gate'))) {
            Gate::authorize(config('filament-translation-manager.gate', config('filament-translation-manager.access.gate')));
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

        // transform to data structure necessary for frontend
        foreach ($translations as $key => $translation) {
            $dataKey = $group.'.'.$key;
            if (! array_key_exists($dataKey, $data)) {
                $data[$dataKey] = [
                    'title' => $group.' - '.$key,
                    'type' => 'group',
                    'group' => $group,
                    'translation_key' => $key,
                    'translations' => [],
                ];
            }
            $data[$dataKey]['translations'][$locale] = $translation;
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('searchTerm')
                    ->hiddenLabel()
                    ->placeholder(trans('filament-translation-manager::messages.search_term_placeholder'))
                    ->prefixIcon('heroicon-o-magnifying-glass'),

                Select::make('selectedGroups')
                    ->hiddenLabel()
                    ->placeholder(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                    ->multiple()
                    ->options(array_combine($this->groups, $this->groups)),
                Select::make('selectedLocales')
                    ->hiddenLabel()
                    ->placeholder(trans('filament-translation-manager::messages.selected_languages_placeholder'))
                    ->multiple()
                    ->options(array_combine($this->locales, $this->locales))
                    ->columnSpan(1),
                Toggle::make('onlyShowMissingTranslations')
                    ->label(trans('filament-translation-manager::messages.only_show_missing_translations_lbl'))
                    ->default(false),
            ])
            ->columns(2);
    }

    public function filterTranslations(): void
    {
        $filteredTranslations = collect($this->getTranslations());
        $this->totalTranslations = $filteredTranslations->count();

        if ($this->searchTerm) {
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) {
                if (Str::contains($translationItem['title'], $this->searchTerm, true)) {
                    return true;
                }

                foreach ($translationItem['translations'] as $translation) {
                    if (Str::contains($translation, $this->searchTerm, true)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($this->onlyShowMissingTranslations) {
            $selectedLocales = $this->getFilteredLocales();
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) use ($selectedLocales) {
                return $this->checkIfTranslationMissing($translationItem['translations'], $selectedLocales);
            });
        }

        if (! empty($this->selectedGroups)) {
            $filteredTranslations = $filteredTranslations->filter(function ($translationItem, $key) {
                return in_array($translationItem['group'], $this->selectedGroups, true);
            });
        }

        $this->countMissingTranslations($filteredTranslations);

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
            $this->pageCounter--;
            $this->filterTranslations();
        }
    }

    public function nextPage(): void
    {
        if ($this->pageCounter * self::PAGE_LIMIT <= $this->totalFilteredTranslations) {
            $this->pageCounter++;
            $this->filterTranslations();
        }
    }

    private function countMissingTranslations($translations): int
    {
        $selectedLocales = $this->getFilteredLocales();

        $count = $translations->reduce(function ($carry, $translationItem) use ($selectedLocales) {
            $missing = $this->checkIfTranslationMissing($translationItem['translations'], $selectedLocales);

            return $carry + ($missing ? 1 : 0);
        }, 0);

        $this->totalMissingFilteredTranslations = $count;

        return $count;
    }

    public function translationsSaved(string $group, string $translationKey, array $newTranslation, ?array $initialTranslations = null): void
    {
        $oldMissing = $this->checkIfTranslationMissing($initialTranslations, $this->getFilteredLocales());
        $newMissing = $this->checkIfTranslationMissing($newTranslation, $this->getFilteredLocales());

        if ($oldMissing && ! $newMissing) {
            $this->totalMissingFilteredTranslations--;
        } elseif (! $oldMissing && $newMissing) {
            $this->totalMissingFilteredTranslations++;
        }
    }

    private function checkIfTranslationMissing(array $translations, array $filteredLocales): bool
    {
        // Check if all selected locales are available in the translation item, by intersecting the locales of the
        // translation item and the selected locales and seeing if the size matches with the selected locales.
        if (count(array_intersect($filteredLocales, array_keys($translations))) !== count($filteredLocales)) {
            return true;
        }

        foreach ($translations as $locale => $translation) {
            if (in_array($locale, $filteredLocales, true)) {
                if (empty($translation) || trim($translation) === '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function getFilteredLocales(): array
    {
        return ! empty($this->selectedLocales) ? $this->selectedLocales : $this->locales;
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-translation-manager.navigation_sort');
    }
}
