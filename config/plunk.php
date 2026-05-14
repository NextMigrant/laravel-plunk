<?php

// config for NextMigrant/Plunk
return [

    /*
    |--------------------------------------------------------------------------
    | Plunk Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Plunk secret API key (sk_*). Required for all endpoints except
    | event tracking which can also use the public key.
    |
    */
    'secret_key' => env('PLUNK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Plunk Public Key
    |--------------------------------------------------------------------------
    |
    | Your Plunk public API key (pk_*). Required for the /v1/track endpoint.
    | The track endpoint does not accept secret keys.
    |
    */
    'public_key' => env('PLUNK_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base API URL
    |--------------------------------------------------------------------------
    |
    | The base API URL for your Plunk instance. All endpoints are relative to
    | this URL (e.g. /contacts, /v1/send, /v1/track). Override this if you
    | are running a self-hosted Plunk instance.
    |
    */
    'base_api_url' => env('PLUNK_BASE_API_URL', 'https://next-api.useplunk.com'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from the API.
    |
    */
    'timeout' => env('PLUNK_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retries for failed requests.
    |
    */
    'retry' => [
        'times' => 3,
        'sleep' => 100, // milliseconds
    ],

];
