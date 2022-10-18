<?php

namespace Statikbe\FilamentTranslationManager\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Statikbe\FilamentTranslationManager\FilamentTranslationManager;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-translate';
    private ChainedTranslationManager $chainedTranslationManager;
    /**
     * @var mixed[]
     */
    public array $groups;
    public array $locales;
    public array $translations;

    protected static function getNavigationGroup(): ?string
    {
        return config('filament-translation-manager.navigation-group', 'settings');
    }

    protected static ?string $navigationGroup = 'settings';

    protected static string $view = 'filament-translation-manager::pages.translation-manager';

    protected static bool $shouldRegisterNavigation = true;

    protected function getTitle(): string {
        return trans('Translations');
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
                    'id' => Str::random(20),
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
}
