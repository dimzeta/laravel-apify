<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Apify API Token
    |--------------------------------------------------------------------------
    |
    | Your Apify API token. You can find this in your Apify account settings.
    | It's recommended to store this in your .env file as APIFY_API_TOKEN.
    |
    */
    'api_token' => env('APIFY_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | The base URI for Apify API requests. You typically won't need to change this.
    |
    */
    'base_uri' => env('APIFY_BASE_URI', 'https://api.apify.com/v2/'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests. Increase this for long-running
    | operations or if you experience timeout issues.
    |
    */
    'timeout' => env('APIFY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Actor Options
    |--------------------------------------------------------------------------
    |
    | Default options for running actors. These can be overridden when calling
    | the runActor method.
    |
    */
    'default_actor_options' => [
        'waitForFinish' => 60, // seconds
        'memory' => 256, // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhooks. Set webhook_url to receive notifications
    | when your actors finish running.
    |
    */
    'webhook_url' => env('APIFY_WEBHOOK_URL'),
    'webhook_events' => [
        'ACTOR.RUN.SUCCEEDED',
        'ACTOR.RUN.FAILED',
        'ACTOR.RUN.ABORTED',
    ],
];
