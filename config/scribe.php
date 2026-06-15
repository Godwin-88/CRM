<?php

return [
    'title' => 'CRM API Documentation',
    'description' => 'API for the Enterprise CRM platform',
    'base_url' => env('APP_URL', 'https://crm.example.com'),
    'routes' => [
        'api' => '/docs/v1',
        'docs' => '/docs',
        'openapi' => '/openapi',
    ],
    'laravel_groups' => [
        'v1' => [
            'prefix' => 'api/v1',
            'middleware' => ['auth:sanctum'],
            'include' => ['api.php'],
        ],
    ],
    'examples' => [
        'auth.bearer' => env('DOCS_AUTH_TOKEN', ''),
    ],
    'fractal' => [
        'serializer' => null,
    ],
    'auth' => [
        [
            'enabled' => true,
            'default' => true,
            'in' => 'header',
            'name' => 'Authorization',
            'description' => 'Bearer token authentication',
            'value' => 'Bearer {YOUR_TOKEN}',
        ],
    ],
    'responseFields' => [],
];
