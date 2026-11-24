<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Database\Connection;
use Mecene\Database\Migrations\Migrator;
use Mecene\Foundation\Application;

#[AsCommand(name: 'migrate', description: 'Exécute les migrations en attente (ou --rollback)')]
class MigrateCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('rollback', null, InputOption::VALUE_NONE, 'Annule le dernier lot de migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Connection $connection */
        $connection = $this->app->make(Connection::class);
        $migrator = new Migrator($connection, $this->app->basePath('database/migrations'));

        if ((bool) $input->getOption('rollback')) {
            $rolledBack = $migrator->rollback();
            if ($rolledBack === []) {
                $output->writeln('<comment>Aucune migration à annuler.</comment>');
                return Command::SUCCESS;
            }
            foreach ($rolledBack as $name) {
                $output->writeln("<info>Rollback effectué : {$name}</info>");
            }
            return Command::SUCCESS;
        }

        $executed = $migrator->run();
        if ($executed === []) {
            $output->writeln('<comment>Base de données déjà à jour. Rien à migrer.</comment>');
            return Command::SUCCESS;
        }

        foreach ($executed as $name) {
            $output->writeln("<info>Migration exécutée : {$name}</info>");
        }

        return Command::SUCCESS;
    }
}
