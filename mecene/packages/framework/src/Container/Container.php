<?php

declare(strict_types=1);

namespace Mecene\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

/**
 * Conteneur d'injection de dépendances compatible PSR-11 avec auto-wiring par réflexion.
 */
class Container implements ContainerInterface
{
    /** @var array<string, array{concrete: Closure|string, shared: bool}> */
    protected array $bindings = [];

    /** @var array<string, mixed> */
    protected array $instances = [];

    /** @var array<string, bool> Pile de résolution pour détecter les dépendances circulaires */
    protected array $buildStack = [];

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $concrete ??= $abstract;

        if (!$concrete instanceof Closure) {
            $concrete = fn (self $container, array $parameters = []): mixed => $container->build($concrete, $parameters);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Le service [{$id}] est introuvable ou non instanciable.");
        }

        try {
            return $this->make($id);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException("Impossible de résoudre [{$id}] : {$e->getMessage()}", 0, $e);
        }
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->bindings[$id])
            || class_exists($id);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if ($parameters === [] && isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $shared = $this->bindings[$abstract]['shared'] ?? false;

        $object = $concrete instanceof Closure
            ? $concrete($this, $parameters)
            : $this->build($concrete, $parameters);

        if ($shared && $parameters === []) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function build(string $concrete, array $parameters = []): mixed
    {
        if (isset($this->buildStack[$concrete])) {
            throw new ContainerException("Dépendance circulaire détectée lors de la construction de [{$concrete}].");
        }

        if (!class_exists($concrete)) {
            throw new NotFoundException("La classe cible [{$concrete}] n'existe pas.");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException("La classe [{$concrete}] n'est pas instanciable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $this->buildStack[$concrete] = true;

        try {
            $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);
            return $reflector->newInstanceArgs($dependencies);
        } finally {
            unset($this->buildStack[$concrete]);
        }
    }

    /**
     * @param callable|array{0: object|string, 1: string} $callback
     * @param array<string, mixed> $parameters
     */
    public function call(callable|array $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            [$target, $method] = $callback;
            $instance = is_string($target) ? $this->make($target) : $target;
            $reflector = new ReflectionMethod($instance, $method);
            $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

            return $reflector->invokeArgs($instance, $dependencies);
        }

        $reflector = new ReflectionFunction(Closure::fromCallable($callback));
        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

        return $reflector->invokeArgs($dependencies);
    }

    /**
     * @param ReflectionParameter[] $reflectionParameters
     * @param array<string, mixed> $primitives
     * @return array<int, mixed>
     */
    protected function resolveDependencies(array $reflectionParameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($reflectionParameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $primitives)) {
                $dependencies[] = $this->castPrimitive($parameter, $primitives[$name]);
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            if ($type !== null && $type->allowsNull()) {
                $dependencies[] = null;
                continue;
            }

            throw new ContainerException("Impossible de résoudre le paramètre [\${$name}].");
        }

        return $dependencies;
    }

    protected function castPrimitive(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            default => $value,
        };
    }
}
