<?php

declare(strict_types=1);

use App\Middlewares\LogRequestMiddleware;
use Mecene\Foundation\Application;
use Mecene\Http\Middleware\StartSession;
use Mecene\Http\Middleware\VerifyCsrfToken;

$app = new Application(dirname(__DIR__));

$app->loadEnvironment();
$app->loadConfiguration();
$app->registerCoreServices();

$app->addGlobalMiddlewares([
    StartSession::class,
    VerifyCsrfToken::class,
    LogRequestMiddleware::class,
]);

$app->registerConfiguredProviders();

return $app;
