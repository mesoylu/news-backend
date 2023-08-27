<?php

return [
    'newsapi' => [
        'className' => 'App\\Services\\News\\NewsapiFetcher',
        'apikey' => env('NEWSAPI_API_KEY'),
        'serviceUrl' => env('NEWSAPI_SERVICE_URL'),
    ],

    'guardian' => [
        'className' => 'App\\Services\\News\\GuardianFetcher',
        'apikey' => env('GUARDIAN_API_KEY'),
        'serviceUrl' => env('GUARDIAN_SERVICE_URL'),
    ],

    'nyt' => [
        'className' => 'App\\Services\\News\\NYTFetcher',
        'apikey' => env('NYT_API_KEY'),
        'serviceUrl' => env('NYT_SERVICE_URL'),
    ],
];
