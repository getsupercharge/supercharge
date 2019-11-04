<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supercharge URL
    |--------------------------------------------------------------------------
    |
    | Configure your Supercharge URL via the `SUPERCHARGE_URL` environment
    | variable.
    |
    | If no URL is defined, Supercharge will stay inactive and source
    | images will be used instead.
    |
    */

    'url' => env('SUPERCHARGE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Bypass Supercharge
    |--------------------------------------------------------------------------
    |
    | If you define the `SUPERCHARGE_BYPASS` environment variable to `1`,
    | Supercharge will be temporarily bypassed and source images will be
    | used instead.
    |
    */

    'bypass' => env('SUPERCHARGE_BYPASS', false),

];
