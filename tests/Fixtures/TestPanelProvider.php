<?php

namespace Statikbe\FilamentTranslationManager\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Statikbe\FilamentTranslationManager\FilamentChainedTranslationManagerPlugin;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugin(FilamentChainedTranslationManagerPlugin::make());
    }
}
