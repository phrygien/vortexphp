<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Mecene'),
    'env' => env('APP_ENV', 'local'),
    'key' => env('APP_KEY', ''),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'Europe/Paris',

    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
];
