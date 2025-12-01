<?php

namespace Statikbe\FilamentTranslationManager\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class TranslationStatusWidget extends Widget
{
    protected string $view = 'filament-translation-manager::widgets.translation-status';

    public static function getSort(): int
    {
        return config('filament-translation-manager.widget.sort') ?? -1;
    }

    public static function canView(): bool
    {
        if (config('filament-translation-manager.widget.gate', config('filament-translation-manager.access.gate'))) {
            return Gate::allows(config('filament-translation-manager.widget.gate', config('filament-translation-manager.access.gate')));
        }

        return true;
    }

    protected function getViewData(): array
    {
        return [
            'missingTranslations' => 10,
        ];
    }
}
