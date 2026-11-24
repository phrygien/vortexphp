<?php

declare(strict_types=1);

namespace Mecene\Http\Middleware;

use Closure;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Session\SessionManager;

/**
 * Démarre la session PHP et fait vieillir les données flash après réponse non-redirectionnelle.
 */
class StartSession implements MiddlewareInterface
{
    public function __construct(
        protected readonly SessionManager $session,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->session->start();

        $response = $next($request);

        if ($response->getStatusCode() < 300 || $response->getStatusCode() >= 400) {
            $this->session->ageFlashData();
        }

        return $response;
    }
}
