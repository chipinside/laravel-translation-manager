<?php

use Barryvdh\TranslationManager\Http\Middleware\Authenticate;

return [
    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    | The default group settings for the elFinder routes.
    |
    */
    'route' => [
        'prefix' => 'translations',
        'middleware' => [
            'web',
            'auth',
            Authenticate::class
        ],
    ],

    /*
     * Enable deletion of translations
     *
     * @type boolean
     */
    'delete_enabled' => true,


    /*
     * The database connection where the translations will be stored for localization
     *
     * @type string
     */
    'connection' => env('TRANSLATIONS_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    /*
     * Exclude specific groups from Laravel Translation Manager.
     * This is useful if, for example, you want to avoid editing the official Laravel language files.
     *
     * @type array
     *
     *    array(
     *        'pagination',
     *        'reminders',
     *        'validation',
     *    )
     */
    'exclude_groups' => [],

    /**
     * Exclude specific languages from Laravel Translation Manager.
     *
     * @type array
     *
     *    array(
     *        'fr',
     *        'de',
     *    )
     */
    'exclude_langs' => [],

    /*
     * Export translations with keys output alphabetically.
     */
    'sort_keys' => false,

    'trans_functions' => [
        'trans',
        'trans_choice',
        'Lang::get',
        'Lang::choice',
        'Lang::trans',
        'Lang::transChoice',
        '@lang',
        '@choice',
        '__',
        '$trans.get',
    ],

    /*
     * Enable pagination of translations
     *
     * @type boolean
     */
    'pagination_enabled' => true,

    /*
     * Define number of translations per page
     *
     * @type integer
     */
    'per_page' => 40,

    /* ------------------------------------------------------------------------------------------------
     | Set Views options
     | ------------------------------------------------------------------------------------------------
     | Here you can set The "extends" blade of index.blade.php
    */
    'layout' => 'translation-manager::layout',

    /*
     * Choose which  template to use [ bootstrap4, bootstrap5, tailwind3 ]
     */
    'template' => 'tailwind3',
];
