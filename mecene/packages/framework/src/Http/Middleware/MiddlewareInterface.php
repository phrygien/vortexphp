<?php

declare(strict_types=1);

namespace Mecene\Http\Middleware;

use Closure;
use Mecene\Http\Request;
use Mecene\Http\Response;

/**
 * Contrat des middlewares Mecene.
 */
interface MiddlewareInterface
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}
