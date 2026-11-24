<?php

declare(strict_types=1);

namespace Mecene\Http\Middleware;

use Closure;
use Mecene\Exceptions\HttpException;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Session\SessionManager;

/**
 * Vérifie la validité du jeton CSRF sur toutes les requêtes Web mutantes.
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    public function __construct(
        protected readonly SessionManager $session,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) || str_starts_with($request->path(), '/api/')) {
            return $next($request);
        }

        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN'));
        $sessionToken = $this->session->token();

        if ($token === '' || !hash_equals($sessionToken, $token)) {
            throw new HttpException(419, 'Jeton CSRF invalide ou expiré. Veuillez recharger la page.');
        }

        return $next($request);
    }
}
