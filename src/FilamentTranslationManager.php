<?php

namespace Statikbe\FilamentTranslationManager;

class FilamentTranslationManager
{
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
