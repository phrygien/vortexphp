<?php

declare(strict_types=1);

namespace App\Middlewares;

use Closure;
use Mecene\Http\Middleware\MiddlewareInterface;
use Mecene\Http\Request;
use Mecene\Http\Response;

class LogRequestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $durationMs = round((microtime(true) - $start) * 1000, 2);

        $logDir = app()->basePath('storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s %s -> %d (%.2f ms)\n",
            date('Y-m-d H:i:s'),
            $request->method(),
            $request->path(),
            $response->getStatusCode(),
            $durationMs
        );

        file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);

        return $response;
    }
}
