<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Foundation\Application;

#[AsCommand(name: 'serve', description: 'Démarre le serveur de développement PHP intégré')]
class ServeCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Adresse d\'écoute', '127.0.0.1');
        $this->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port HTTP', '8000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = (string) $input->getOption('host');
        $port = (string) $input->getOption('port');
        $publicDir = $this->app->basePath('public');

        $output->writeln("<info>Serveur Mecene démarré sur http://{$host}:{$port}</info>");
        $output->writeln('<comment>Appuyez sur Ctrl+C pour arrêter.</comment>');

        $command = sprintf(
            '%s -S %s:%s -t %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($publicDir)
        );

        passthru($command, $exitCode);

        return (int) $exitCode;
    }
}
