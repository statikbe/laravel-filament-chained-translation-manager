<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationManagerPage extends Page
{
    const PAGE_LIMIT = 20;

    protected static ?string $navigationIcon = 'heroicon-o-translate';
    private ChainedTranslationManager $chainedTranslationManager;
    /**
     * @var mixed[]
     */
    public array $groups;
    public array $locales;
    public array $translations;

    //filters
    public string $searchTerm = '';
    public bool $onlyShowMissingTranslations = false;
    public array $selectedGroups = [];
    public array $selectedLanguages = [];
    public int $pageCounter = 1;

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
            'except' => 0,
        ],
        'selectedGroups',
        'selectedLanguages',
    ];

    protected static string $view = 'filament-translation-manager::pages.translation-manager-page';

    protected static bool $shouldRegisterNavigation = true;

    protected static function getNavigationGroup(): ?string
    {
        return config('filament-translation-manager.navigation-group', 'settings');
    }

    protected function getTitle(): string
    {
        return trans('filament-translation-manager::messages.title');
    }

    public function mount(ChainedTranslationManager $chainedTranslationManager): void
    {
        $this->chainedTranslationManager = $chainedTranslationManager;
        $this->loadInitialData();
    }

    private function loadInitialData()
    {
        $groups = $this->chainedTranslationManager->getTranslationGroups();
        $this->groups = collect($groups)->diff(config('filament-translation-manager.ignore_groups',[]))->values()->toArray();

        $this->locales = $this->getLocalesData();

        $this->translations = $this->getTranslations();
    }

    /**
     * Returns a list of the configured, supported locales (key: locale) with their names (key: language).
     * @return array
     */
    protected function getLocalesData(): array
    {
        $locales = FilamentTranslationManager::getLocales();
        $localesData = [];

        foreach($locales as $locale){
            $localesData[] = [
                'locale' => $locale,
                'language' => trans($locale),
            ];
        }

        return $localesData;
    }

    private function getTranslations(): array
    {
        $data = [];
        foreach ($this->locales as $language) {
            foreach ($this->groups as $group) {
                $this->addTranslationsToData($data, $language, $group);
            }
        }

        return array_slice(array_values($data),0, 20);
    }


    private function addTranslationsToData(array &$data, array $language, string $group): array
    {
        $translations = $this->chainedTranslationManager->getTranslationsForGroup($language['locale'], $group);

        //transform to data structure necessary for frontend
        foreach ($translations as $key => $translation) {
            $dataKey = $group.'.'.$key;
            if (!array_key_exists($dataKey, $data)) {
                $data[$dataKey] = [
                    'title' => $group.' - '. $key,
                    'type' => 'group',
                    'group' => $group,
                    'key' => $key,
                    'translations' => [],
                ];
            }
            $data[$dataKey]['translations'][$language['locale']] = $translation;
        }

        return $data;
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('searchTerm')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.search_term_placeholder')),
            Checkbox::make('onlyShowMissingTranslations')
                ->label('filament-translation-manager::messages.only_show_missing_translations_lbl')
                ->default(false),
            Select::make('selectedGroups')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.selected_groups_placeholder'))
                ->multiple()
                ->options($this->groups),
            Select::make('selectedLanguages')
                ->disableLabel()
                ->placeholder(trans('filament-translation-manager::messages.selected_languages_placeholder'))
                ->multiple()
                ->options($this->locales),
        ];
    }

    public function filterTranslations(): void
    {
        //TODO
    }
}
