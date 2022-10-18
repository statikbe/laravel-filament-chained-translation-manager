<?php

namespace Statikbe\FilamentTranslationManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Statikbe\FilamentTranslationManager\FilamentTranslationManager
 */
class FilamentTranslationManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Statikbe\FilamentTranslationManager\FilamentTranslationManager::class;
    }
}
