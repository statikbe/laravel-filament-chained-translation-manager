<?php

return [
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Application Supported Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the possible locales that can be used.
    | You are free to fill this array with any of the locales which will be
    | supported by the application.
    |
    */
    'supported_locales' => [
        'en',
        'nl',
        'fr',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access
    |--------------------------------------------------------------------------
    |
    | Limited = false (default)
    |   Anyone can use the translation manager.
    |
    | Limited = true
    |   The page will use the provided gate to see if the user has access.
    |   - Default Laravel: you can define the gate in a service provider
            (https://laravel.com/docs/9.x/authorization)
    |   - Spatie permissions: set the 'gate' variable to a permission name you want to check against, see the example below.
    |
    |
    */
    'access' => [
        'limited' => false,
        //'gate' => 'view-filament-translation-manager',
    ],

    /*
     |--------------------------------------------------------------------------
     | Ignore Groups
     |--------------------------------------------------------------------------
     |
     | You can list the translation groups that you do not want users to translate.
     | Note: the JSON files are grouped in 'json-file' by default. (see config/laravel-chained-translator.php)
     */
    'ignore_groups' => [
        //        'auth',
    ],

    'navigation_sort' => null,
];
