<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Foundation\Application;

#[AsCommand(name: 'make:model', description: 'Génère un modèle Eloquent dans app/Models à partir du stub du framework')]
class MakeModelCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nom du modèle au singulier (ex: Comment)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = ucfirst(trim((string) $input->getArgument('name')));
        $table = strtolower($name) . 's';

        $dir = $this->app->basePath('app/Models');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $target = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($target)) {
            $output->writeln("<error>Le modèle [{$name}] existe déjà.</error>");
            return Command::FAILURE;
        }

        $stubPath = dirname(__DIR__, 3) . '/stubs/model.stub';
        $stub = file_get_contents($stubPath);
        if ($stub === false) {
            $output->writeln("<error>Stub introuvable : {$stubPath}</error>");
            return Command::FAILURE;
        }

        $content = str_replace(['{{class}}', '{{table}}'], [$name, $table], $stub);
        file_put_contents($target, $content);

        $output->writeln("<info>Modèle créé : app/Models/{$name}.php</info>");
        return Command::SUCCESS;
    }
}
