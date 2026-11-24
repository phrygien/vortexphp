<?php

declare(strict_types=1);

namespace Mecene\Routing;

use Closure;
use InvalidArgumentException;
use Mecene\Container\Container;
use Mecene\Exceptions\HttpException;
use Mecene\Http\Middleware\Pipeline;
use Mecene\Http\Request;
use Mecene\Http\Response;

/**
 * Routeur HTTP générique : verbes REST, groupes, préfixes, paramètres et routes nommées.
 */
class Router
{
    /** @var array<string, Route[]> */
    protected array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    /** @var array<int, array{prefix: string, middleware: array<int, string|callable>}> */
    protected array $groupStack = [];

    public function __construct(
        protected readonly Container $container,
    ) {
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    public function get(string $uri, Closure|array $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    public function post(string $uri, Closure|array $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    public function put(string $uri, Closure|array $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    public function patch(string $uri, Closure|array $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    public function delete(string $uri, Closure|array $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * @param array{prefix?: string, middleware?: string|array<int, string|callable>} $attributes
     */
    public function group(array $attributes, Closure $callback): void
    {
        $parentPrefix = $this->currentPrefix();
        $parentMiddleware = $this->currentMiddlewares();

        $prefix = trim((string) ($attributes['prefix'] ?? ''), '/');
        $fullPrefix = trim($parentPrefix . '/' . $prefix, '/');

        $middleware = $attributes['middleware'] ?? [];
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }

        $this->groupStack[] = [
            'prefix' => $fullPrefix,
            'middleware' => array_merge($parentMiddleware, $middleware),
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     */
    protected function addRoute(string $method, string $uri, Closure|array $action): Route
    {
        $prefix = $this->currentPrefix();
        $cleanUri = trim($uri, '/');
        $fullUri = '/' . trim(($prefix !== '' ? $prefix . '/' : '') . $cleanUri, '/');
        if ($fullUri === '') {
            $fullUri = '/';
        }

        $route = new Route($method, $fullUri, $action, $this->currentMiddlewares());
        $this->routes[$method][] = $route;

        return $route;
    }

    protected function currentPrefix(): string
    {
        if ($this->groupStack === []) {
            return '';
        }
        return $this->groupStack[array_key_last($this->groupStack)]['prefix'];
    }

    /**
     * @return array<int, string|callable>
     */
    protected function currentMiddlewares(): array
    {
        if ($this->groupStack === []) {
            return [];
        }
        return $this->groupStack[array_key_last($this->groupStack)]['middleware'];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $route->matches($path);
            if ($params !== null) {
                $request->setRouteParams($params);

                return (new Pipeline($this->container))
                    ->send($request)
                    ->through($route->getMiddlewares())
                    ->then(function (Request $req) use ($route, $params): Response {
                        $result = $this->container->call($route->action, $params);
                        return $this->normalizeResponse($result);
                    });
            }
        }

        throw new HttpException(404, "Aucune route trouvée pour [{$method} {$path}].");
    }

    /**
     * @param array<string, string|int> $parameters
     */
    public function url(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                if ($route->getName() === $name) {
                    $uri = $route->uri;
                    foreach ($parameters as $key => $value) {
                        $uri = str_replace('{' . $key . '}', urlencode((string) $value), $uri);
                    }
                    return $uri;
                }
            }
        }

        throw new InvalidArgumentException("La route nommée [{$name}] n'existe pas.");
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        $all = [];
        foreach ($this->routes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $all[] = $route;
            }
        }
        return $all;
    }

    protected function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }
}
