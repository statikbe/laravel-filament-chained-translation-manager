<?php

declare(strict_types=1);

namespace Statikbe\FilamentTranslationManager\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

class TranslationStatusWidget extends StatsOverviewWidget
{
    public static function getSort(): int
    {
        return FilamentChainedTranslationManagerPlugin::get()->getWidgetSort() ?? -1;
    }

    public static function canView(): bool
    {
        $gate = FilamentChainedTranslationManagerPlugin::get()->getWidgetGate();

        return $gate ? Gate::allows($gate) : true;
    }

    protected function getStats(): array
    {
        $plugin = FilamentChainedTranslationManagerPlugin::get();
        $locales = $plugin->getLocales();
        $sourceLocale = $plugin->getSourceLocale();
        $translatorLocales = array_values(array_filter($locales, static fn($l) => $l !== $sourceLocale));

        $manager = app(ChainedTranslationManager::class);
        $ignoreGroups = $plugin->getIgnoreGroups();
        $groups = collect($manager->getTranslationGroups())->diff($ignoreGroups)->values()->all();

        $stats = [];

        foreach ($translatorLocales as $locale) {
            $total = 0;
            $missing = 0;

            foreach ($groups as $group) {
                $sourceTranslations = $manager->getTranslationsForGroup($sourceLocale, $group);
                $localeTranslations = $manager->getTranslationsForGroup($locale, $group);

                foreach ($sourceTranslations as $key) {
                    $total++;

                    $localeValue = $localeTranslations[$key] ?? null;

                    if (blank($localeValue)) {
                        $missing++;
                    }
                }
            }

            $translated = $total - $missing;
            $percentage = $total > 0 ? round(($translated / $total) * 100) : 100;

            $color = match (true) {
                $percentage === 100 => 'success',
                $percentage >= 75 => 'warning',
                default => 'danger',
            };

            $stats[] = Stat::make(label: strtoupper($locale), value: "{$percentage}%")
                ->description(trans('filament-translation-manager::messages.widget_stat_description', [
                    'translated' => $translated,
                    'total' => $total,
                    'missing' => $missing,
                ]))
                ->color($color)
                ->chart([$missing, $translated]);
        }

        return $stats;
    }
}
