<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Foundation\Application;

#[AsCommand(name: 'make:migration', description: 'Génère une migration horodatée dans database/migrations à partir du stub')]
class MakeMigrationCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Nom de la migration (ex: create_comments_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = strtolower(trim((string) $input->getArgument('name')));
        $table = 'items';

        if (preg_match('/create_([a-z0-9_]+)_table/', $name, $matches) === 1) {
            $table = $matches[1];
        }

        $dir = $this->app->basePath('database/migrations');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stubPath = dirname(__DIR__, 3) . '/stubs/migration.stub';
        $stub = file_get_contents($stubPath);
        if ($stub === false) {
            $output->writeln("<error>Stub introuvable : {$stubPath}</error>");
            return Command::FAILURE;
        }

        $filename = date('Y_m_d_His') . "_{$name}.php";
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        $content = str_replace('{{table}}', $table, $stub);
        file_put_contents($target, $content);

        $output->writeln("<info>Migration créée : database/migrations/{$filename}</info>");
        return Command::SUCCESS;
    }
}
