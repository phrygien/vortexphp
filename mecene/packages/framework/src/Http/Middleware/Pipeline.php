<?php

declare(strict_types=1);

namespace Mecene\Http\Middleware;

use Closure;
use Mecene\Container\Container;
use Mecene\Http\Request;
use Mecene\Http\Response;

/**
 * Pipeline d'exécution en oignon des middlewares HTTP.
 */
class Pipeline
{
    protected Request $passable;

    /** @var array<int, string|callable|MiddlewareInterface> */
    protected array $pipes = [];

    public function __construct(
        protected readonly Container $container,
    ) {
    }

    public function send(Request $request): self
    {
        $this->passable = $request;
        return $this;
    }

    /**
     * @param array<int, string|callable|MiddlewareInterface> $pipes
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * @param Closure(Request): Response $destination
     */
    public function then(Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            fn (Closure $next, string|callable|MiddlewareInterface $pipe): Closure => function (Request $request) use ($pipe, $next): Response {
                if (is_string($pipe)) {
                    $instance = $this->container->make($pipe);
                    return $instance->handle($request, $next);
                }

                if ($pipe instanceof MiddlewareInterface) {
                    return $pipe->handle($request, $next);
                }

                return $pipe($request, $next);
            },
            $destination
        );

        return $pipeline($this->passable);
    }
}
