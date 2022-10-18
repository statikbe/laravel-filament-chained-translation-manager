<?php

namespace Statikbe\FilamentTranslationManager\Commands;

use Illuminate\Console\Command;

class FilamentTranslationManagerCommand extends Command
{
    public $signature = 'laravel-filament-chained-translation-manager';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
