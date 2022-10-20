<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use Filament\Forms\Components\Checkbox;
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
     * @var array<{ 'title': string, 'type': string, 'group': string, 'key': string, 'translations': array<string, string> }>
     */
    public array $translations;
    /**
     * @var Collection<{ 'title': string, 'type': string, 'group': string, 'key': string, 'translations': array<string, string> }>
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
        ],
        'selectedGroups',
        'selectedLocales',
    ];

    protected static string $view = 'filament-translation-manager::pages.translation-manager-page';

    protected static function shouldRegisterNavigation(): bool
    {
        if (config('filament-translation-manager.access.limited')){
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
        if (config('filament-translation-manager.access.limited')){
            Gate::authorize(config('filament-translation-manager.access.gate'));
        }

        $this->loadInitialData();
    }

    private function loadInitialData()
    {
        $groups = $this->getChainedTranslationManager()->getTranslationGroups();
        $this->groups = collect($groups)->diff(config('filament-translation-manager.ignore_groups',[]))->values()->toArray();

        $this->locales = $this->getLocalesData();
        $this->selectedLocales = $this->locales;

        $this->filterTranslations();
    }

    /**
     * Returns a list of the configured, supported locales (key: locale) with their names (key: language).
     * @return array
     */
    protected function getLocalesData(): array
    {
        return FilamentTranslationManager::getLocales();
    }

    private function getTranslations(): array
    {
        $data = [];
        $availableGroups = $this->groups;
        if(!empty($this->selectedGroups)){
            $availableGroups = $this->selectedGroups;
        }

        $availableLocales = $this->locales;
        if(!empty($this->selectedLocales)){
            $availableLocales = $this->selectedLocales;
        }

        foreach ($availableLocales as $locale) {
            foreach ($availableGroups as $group) {
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
            if (!array_key_exists($dataKey, $data)) {
                $data[$dataKey] = [
                    'title' => $group.' - '. $key,
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
            TextInput::make('searchTerm')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.search_term_placeholder'))
                ->prefixIcon('heroicon-o-search'),
            Checkbox::make('onlyShowMissingTranslations')
                ->label(trans('filament-translation-manager::messages.only_show_missing_translations_lbl'))
                ->default(false),
            Select::make('selectedGroups')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                ->multiple()
                ->options(array_combine($this->groups, $this->groups)),
            Select::make('selectedLocales')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.selected_languages_placeholder'))
                ->multiple()
                ->options(array_combine($this->locales, $this->locales)),
        ];
    }

    public function filterTranslations(): void
    {
        $filteredTranslations = collect($this->getTranslations());
        if($this->searchTerm){
            $filteredTranslations = $filteredTranslations->filter(function($translationItem, $key){
                foreach($translationItem['translations'] as $translation){
                    if(Str::contains($translation, $this->searchTerm, true)){
                        return true;
                    }
                }
                return false;
            });
        }

        if($this->onlyShowMissingTranslations){
            $filteredTranslations = $filteredTranslations->filter(function($translationItem, $key){
                //TODO check for selected locales:
                foreach($translationItem['translations'] as $locale => $translation){
                    if(empty($translation) || trim($translation) === ''){
                        return true;
                    }
                }
                return false;
            });
        }

        $filteredTranslations = $this->paginateTranslations($filteredTranslations);

        $this->filteredTranslations = $filteredTranslations;
    }

    private function paginateTranslations(Collection $translations): Collection {
        $translations = $translations->sortBy([
            ['group', 'asc'],
            ['key', 'asc'],
        ]);

        $offset = 0;
        if($this->pageCounter > 1){
            $offset = ($this->pageCounter - 1) * self::PAGE_LIMIT;
        }

        $this->pagedTranslations = $offset + self::PAGE_LIMIT;
        $this->totalFilteredTranslations = count($translations);

        return $translations->slice($offset, self::PAGE_LIMIT);
    }

    private function getChainedTranslationManager()
    {
        if (!isset($this->chainedTranslationManager)){
            $this->chainedTranslationManager = app(ChainedTranslationManager::class);
        }

        return $this->chainedTranslationManager;
    }

    public function previousPage(){
        if($this->pageCounter > 1) {
            $this->pageCounter -= 1;
            $this->filterTranslations();
        }
    }

    public function nextPage(){
        if($this->pageCounter * self::PAGE_LIMIT <= $this->totalFilteredTranslations) {
            $this->pageCounter += 1;
            $this->filterTranslations();
        }
    }
}
