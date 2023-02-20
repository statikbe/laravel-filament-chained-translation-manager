<?php

namespace Statikbe\FilamentTranslationManager\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class TranslationStatusWidget extends Widget
{
    protected static string $view = 'filament-translation-manager::widgets.translation-status';

    public static function getSort(): int
    {
        return 200; //config('filament-translation-manager.widget.sort', static::$sort);
    }

    public static function canView(): bool
    {
        if (config('filament-translation-manager.access.limited')) {
            return Gate::allows(config('filament-translation-manager.access.gate'));
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
