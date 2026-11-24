<?php

declare(strict_types=1);

namespace Mecene\Foundation;

use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Throwable;
use Mecene\Config\Repository as ConfigRepository;
use Mecene\Container\Container;
use Mecene\Database\Connection;
use Mecene\Database\ORM\Model;
use Mecene\Exceptions\Handler;
use Mecene\Http\Middleware\Pipeline;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Routing\Router;
use Mecene\Session\SessionManager;
use Mecene\Support\ServiceProvider;
use Mecene\View\MeceneCompiler;
use Mecene\View\ViewFactory;

/**
 * Noyau applicatif générique : aucune dépendance vers le code métier de l'application hôte.
 */
class Application extends Container
{
    public const string VERSION = '1.0.0';

    protected static ?self $instance = null;

    /** @var ServiceProvider[] */
    protected array $providers = [];

    protected bool $booted = false;

    /** @var array<int, string|callable> */
    protected array $globalMiddlewares = [];

    public function __construct(
        protected readonly string $basePath,
    ) {
        self::$instance = $this;
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(ContainerInterface::class, $this);
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $path === '' ? $this->basePath : $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    public function loadEnvironment(): void
    {
        if (file_exists($this->basePath('.env'))) {
            $dotenv = Dotenv::createImmutable($this->basePath());
            $dotenv->safeLoad();
        }
    }

    public function loadConfiguration(): void
    {
        $config = ConfigRepository::loadFromDirectory($this->basePath('config'));
        $this->instance(ConfigRepository::class, $config);
        $this->instance('config', $config);

        date_default_timezone_set((string) $config->get('app.timezone', 'UTC'));
    }

    public function registerCoreServices(): void
    {
        $this->singleton(SessionManager::class, fn () => new SessionManager());

        $this->singleton(Connection::class, function (self $app) {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $connection = new Connection($config, $app->basePath());
            Model::setConnection($connection);
            return $connection;
        });

        $this->singleton(Router::class, fn (self $app) => new Router($app));

        $this->singleton(ViewFactory::class, function (self $app) {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $paths = (array) $config->get('view.paths', [$app->basePath('resources/views')]);
            $compiled = (string) $config->get('view.compiled', $app->basePath('storage/cache'));

            return new ViewFactory(
                new MeceneCompiler($compiled),
                $paths,
                $app->make(SessionManager::class),
            );
        });

        $this->singleton(Handler::class, fn (self $app) => new Handler($app));

        $this->make(Connection::class);
    }

    public function registerConfiguredProviders(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->make(ConfigRepository::class);
        $providerClasses = (array) $config->get('app.providers', []);

        foreach ($providerClasses as $providerClass) {
            /** @var ServiceProvider $provider */
            $provider = new $providerClass($this);
            $provider->register();
            $this->providers[] = $provider;
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @param array<int, string|callable> $middlewares
     */
    public function addGlobalMiddlewares(array $middlewares): void
    {
        $this->globalMiddlewares = array_merge($this->globalMiddlewares, $middlewares);
    }

    public function handle(Request $request): Response
    {
        $this->instance(Request::class, $request);

        try {
            /** @var Router $router */
            $router = $this->make(Router::class);

            return (new Pipeline($this))
                ->send($request)
                ->through($this->globalMiddlewares)
                ->then(fn (Request $req): Response => $router->dispatch($req));
        } catch (Throwable $e) {
            /** @var Handler $handler */
            $handler = $this->make(Handler::class);
            return $handler->render($request, $e);
        }
    }
}
