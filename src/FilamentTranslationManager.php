<?php

namespace Statikbe\FilamentTranslationManager;

class FilamentTranslationManager
{
    /**
     * The locales array for the tool.
     */
    public static array $locales;

    public static function setLocales(array $locales): void
    {
        static::$locales = $locales;
    }

    public static function getLocales(): array
    {
        return static::$locales;
    }
}
