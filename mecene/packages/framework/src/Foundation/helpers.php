<?php

declare(strict_types=1);

use Mecene\Config\Repository;
use Mecene\Exceptions\HttpException;
use Mecene\Foundation\Application;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Routing\Router;
use Mecene\Session\SessionManager;
use Mecene\View\ViewFactory;

if (!function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $instance = Application::getInstance();

        if ($instance === null) {
            throw new RuntimeException('L\'application Mecene n\'est pas encore initialisée.');
        }

        return $abstract === null ? $instance : $instance->make($abstract, $parameters);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var Repository $repository */
        $repository = app(Repository::class);
        return $key === null ? $repository : $repository->get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * @param array<string, mixed> $data
     */
    function view(string $template, array $data = [], int $status = 200): Response
    {
        /** @var ViewFactory $factory */
        $factory = app(ViewFactory::class);
        return Response::make($factory->render($template, $data), $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}

if (!function_exists('redirect')) {
    function redirect(?string $url = null, int $status = 302): Response
    {
        if ($url === null) {
            $referer = app(Request::class)->header('Referer', '/');
            return Response::redirect($referer, $status);
        }

        return Response::redirect($url, $status);
    }
}

if (!function_exists('route')) {
    /**
     * @param array<string, string|int> $parameters
     */
    function route(string $name, array $parameters = []): string
    {
        /** @var Router $router */
        $router = app(Router::class);
        return $router->url($name, $parameters);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        /** @var SessionManager $session */
        $session = app(SessionManager::class);
        return $key === null ? $session : $session->get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        session()->flash($key, $value);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return session()->old($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
            echo '<pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;font-family:monospace;font-size:13px;line-height:1.5;overflow:auto;">';
            foreach ($vars as $var) {
                var_dump($var);
            }
            echo '</pre>';
        } else {
            foreach ($vars as $var) {
                var_dump($var);
            }
        }

        exit(1);
    }
}
