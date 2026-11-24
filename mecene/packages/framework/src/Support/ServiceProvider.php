<?php

declare(strict_types=1);

namespace Mecene\Support;

use Mecene\Foundation\Application;

/**
 * Classe abstraite des Service Providers (cycle register -> boot).
 */
abstract class ServiceProvider
{
    public function __construct(
        protected readonly Application $app,
    ) {
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
