<?php

declare(strict_types=1);

namespace Mecene\Routing;

use Closure;

/**
 * Représente une route HTTP enregistrée dans le routeur.
 */
class Route
{
    protected ?string $name = null;

    /** @var array<int, string|callable> */
    protected array $middlewares = [];

    /** @var string[] */
    protected array $parameterNames = [];

    protected string $compiledRegex;

    /**
     * @param Closure|array{0: class-string, 1: string} $action
     * @param array<int, string|callable> $middlewares
     */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly Closure|array $action,
        array $middlewares = [],
    ) {
        $this->middlewares = $middlewares;
        $this->compilePattern();
    }

    protected function compilePattern(): void
    {
        $normalized = '/' . trim($this->uri, '/');
        if ($normalized === '') {
            $normalized = '/';
        }

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $matches): string {
                $this->parameterNames[] = $matches[1];
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $normalized
        );

        $this->compiledRegex = '#^' . $pattern . '$#';
    }

    /**
     * @return array<string, string>|null
     */
    public function matches(string $path): ?array
    {
        if (preg_match($this->compiledRegex, $path, $matches) !== 1) {
            return null;
        }

        $params = [];
        foreach ($this->parameterNames as $name) {
            if (isset($matches[$name])) {
                $params[$name] = urldecode($matches[$name]);
            }
        }

        return $params;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|array<int, string|callable> $middleware
     */
    public function middleware(string|array $middleware): self
    {
        $list = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $list);
        return $this;
    }

    /**
     * @return array<int, string|callable>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
