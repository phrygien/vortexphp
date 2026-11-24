<?php

declare(strict_types=1);

namespace App\Providers;

use Mecene\Routing\Router;
use Mecene\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $routesFile = $this->app->basePath('routes/web.php');
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }
}
