<?php

namespace Statikbe\FilamentTranslationManager;

class FilamentTranslationManager
{
    /**
     * The locales array for the tool.
     *
     * @var array
     */
    public static $locales;

    /**
     * @param  array  $locales
     */
    public static function setLocales(array $locales)
    {
        static::$locales = $locales;
    }

    /**
     * @return array
     */
    public static function getLocales(): array
    {
        return static::$locales;
    }
}
