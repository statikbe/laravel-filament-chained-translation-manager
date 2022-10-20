<?php

return [
    'enabled' => true,

    'supported_locales' => [
        'en',
        'nl',
        'fr'
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
    |   - Spatie permissions: fill gate with a permission you want to check against
    |
    |
    */
    'access' => [
        'limited' => false,
        //'gate' => 'view-filament-translation-manager',
    ],

    'ignore_groups' => [
        'single'
    ],
];
