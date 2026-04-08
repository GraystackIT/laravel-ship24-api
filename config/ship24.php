<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ship24 API Key
    |--------------------------------------------------------------------------
    | Your Ship24 API key (Bearer token).
    | Get one at: https://app.ship24.com/
    */
    'api_key' => env('SHIP24_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    | Override only when using a proxy or staging environment.
    */
    'base_url' => env('SHIP24_BASE_URL', 'https://api.ship24.com/public/v1'),
];
