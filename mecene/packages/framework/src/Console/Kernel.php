<?php

declare(strict_types=1);

namespace Mecene\Console;

use Symfony\Component\Console\Application as SymfonyConsole;
use Mecene\Console\Commands\KeyGenerateCommand;
use Mecene\Console\Commands\MakeControllerCommand;
use Mecene\Console\Commands\MakeMigrationCommand;
use Mecene\Console\Commands\MakeModelCommand;
use Mecene\Console\Commands\MigrateCommand;
use Mecene\Console\Commands\ServeCommand;
use Mecene\Foundation\Application;

/**
 * Noyau CLI "artisan" du framework Mecene basé sur symfony/console.
 */
class Kernel
{
    protected SymfonyConsole $console;

    public function __construct(
        protected readonly Application $app,
    ) {
        $this->console = new SymfonyConsole('Mecene Artisan CLI', Application::VERSION);
        $this->registerCommands();
    }

    protected function registerCommands(): void
    {
        $this->console->add(new MakeControllerCommand($this->app));
        $this->console->add(new MakeModelCommand($this->app));
        $this->console->add(new MakeMigrationCommand($this->app));
        $this->console->add(new MigrateCommand($this->app));
        $this->console->add(new ServeCommand($this->app));
        $this->console->add(new KeyGenerateCommand($this->app));
    }

    public function run(): int
    {
        return $this->console->run();
    }
}
