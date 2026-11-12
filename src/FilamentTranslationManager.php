<?php

namespace Statikbe\FilamentTranslationManager;

use Illuminate\Support\Facades\Gate;
use Statikbe\LaravelChainedTranslator\ChainedTranslationManager;

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

    /**
     * Returns the gate that protects the translation manager, or null if no gate is configured.
     */
    public static function getGate(): ?string
    {
        $gate = config('filament-translation-manager.gate', config('filament-translation-manager.access.gate'));

        return is_string($gate) && $gate !== '' ? $gate : null;
    }

    /**
     * Checks if the current user may use the translation manager.
     */
    public static function canAccess(): bool
    {
        $gate = static::getGate();

        return $gate === null || Gate::allows($gate);
    }

    /**
     * Aborts with a 403 if the current user may not use the translation manager.
     */
    public static function authorize(): void
    {
        $gate = static::getGate();

        if ($gate !== null) {
            Gate::authorize($gate);
        }
    }

    /**
     * Checks if the locale is one of the configured locales.
     */
    public static function isAllowedLocale(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, static::getLocales(), true);
    }

    /**
     * Returns the translation groups that may be shown and edited (all groups except the ignored ones).
     *
     * @return array<int, string>
     */
    public static function getAllowedGroups(): array
    {
        $groups = app(ChainedTranslationManager::class)->getTranslationGroups();

        return collect($groups)
            ->diff(config('filament-translation-manager.ignore_groups', []))
            ->values()
            ->toArray();
    }

    /**
     * Checks if the translation group may be shown and edited.
     */
    public static function isAllowedGroup(mixed $group): bool
    {
        return is_string($group) && in_array($group, static::getAllowedGroups(), true);
    }

    /**
     * Keeps only the string values that are in the allowed list.
     *
     * @return array<int, string>
     */
    public static function filterAllowedValues(mixed $values, array $allowed): array
    {
        if (! is_array($values)) {
            return [];
        }

        $values = array_filter($values, fn ($value) => is_string($value) && in_array($value, $allowed, true));

        return array_values(array_unique($values));
    }
}
