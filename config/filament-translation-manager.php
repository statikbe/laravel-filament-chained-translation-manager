<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Supported Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the possible locales that can be used.
    | You are free to fill this array with any of the locales which will be
    | supported by the application.
    |
    | Default: The available and fallback locale
    |
    */
    'locales' => [
        // 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gate
    |--------------------------------------------------------------------------
    |
    | The page will use the provided gate to see if the user has access.
    | Note: you can define the gate in a service provider
    | (visit: https://laravel.com/docs/12.x/authorization)
    |
    */
    'gate' => null,

    /*
     |--------------------------------------------------------------------------
     | Ignore Groups
     |--------------------------------------------------------------------------
     |
     | You can list the translation groups that you do not want users to translate.
     | Note: the JSON files are grouped in 'json-file' by default.
     | (see: config/laravel-chained-translator.php)
     |
     */
    'ignore_groups' => [
        // 'auth',
    ],

    /*
     |--------------------------------------------------------------------------
     | Navigation Sort
     |--------------------------------------------------------------------------
     |
     | You can specify the order in which navigation items are listed.
     | Accepts integer value according to Filament documentation.
     | (visit: https://filamentphp.com/docs/4.x/navigation/overview#sorting-navigation-items)
     |
     */
    'navigation_sort' => null,

    /*
     |--------------------------------------------------------------------------
     | Navigation Group
     |--------------------------------------------------------------------------
     |
     | You can specify the group in which navigation items are listed.
     | Accepts a string with the translation key.
     | Another option is to overwrite the 'navigation_group' key in the translation file
     | (visit: https://filamentphp.com/docs/4.x/navigation/overview#sorting-navigation-items)
     |
     */
    'navigation_group' => 'filament-translation-manager::messages.navigation_group',

    /*
     |--------------------------------------------------------------------------
     | Widget
     |--------------------------------------------------------------------------
     |
     | You can specify the widget settings:
     | - Enable the widget.
     | - Define the gate to see if the user has access.
     | - Specify the order in which the widget is listed.
     |
     */
    'widget' => [
        'enabled' => false,
        'gate' => null,
        'sort' => null,
    ],

    /*
     |--------------------------------------------------------------------------
     | Navigation Icon
     |--------------------------------------------------------------------------
     |
     | You can specify the navigation icon.
     | (visit: https://blade-ui-kit.com/blade-icons?set=1#search)
     | You can set the custom navigation icon with the Filament class: Heroicon, use another enum of your icon font or a string.
     | Default is this the Outlined version of language
     | For null value, the icon will be hidden.
     |
     */
    'navigation_icon' => \Filament\Support\Icons\Heroicon::OutlinedLanguage,
];
