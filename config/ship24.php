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

    /*
    |--------------------------------------------------------------------------
    | Tracking Mode
    |--------------------------------------------------------------------------
    | Controls how tracking data is persisted locally when using the
    | IsTrackableByShip24 trait:
    |
    |   'latest'  — overwrites with the most recent status only (default)
    |   'history' — stores the full events array in the events JSON column
    */
    'tracking_mode' => env('SHIP24_TRACKING_MODE', 'latest'),

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    | Configure the inbound webhook endpoint that receives Ship24 push updates.
    | Set SHIP24_WEBHOOK_SECRET to enable HMAC-SHA256 signature validation.
    */
    'webhook' => [
        'enabled' => env('SHIP24_WEBHOOK_ENABLED', true),
        'path'    => env('SHIP24_WEBHOOK_PATH', 'ship24/webhook'),
        'secret'  => env('SHIP24_WEBHOOK_SECRET', null),
    ],
];
