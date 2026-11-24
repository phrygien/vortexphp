<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Foundation\Application;

#[AsCommand(name: 'make:controller', description: 'Génère un contrôleur HTTP dans app/Controllers à partir du stub du framework')]
class MakeControllerCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nom du contrôleur (ex: CommentController)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $dir = $this->app->basePath('app/Controllers');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $target = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($target)) {
            $output->writeln("<error>Le contrôleur [{$name}] existe déjà.</error>");
            return Command::FAILURE;
        }

        $stubPath = dirname(__DIR__, 3) . '/stubs/controller.stub';
        $stub = file_get_contents($stubPath);
        if ($stub === false) {
            $output->writeln("<error>Stub introuvable : {$stubPath}</error>");
            return Command::FAILURE;
        }

        $content = str_replace('{{class}}', $name, $stub);
        file_put_contents($target, $content);

        $output->writeln("<info>Contrôleur créé : app/Controllers/{$name}.php</info>");
        return Command::SUCCESS;
    }
}
