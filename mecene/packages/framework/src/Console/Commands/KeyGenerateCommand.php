<?php

declare(strict_types=1);

namespace Mecene\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mecene\Foundation\Application;

#[AsCommand(name: 'key:generate', description: 'Génère une clé applicative cryptographique APP_KEY dans le fichier .env')]
class KeyGenerateCommand extends Command
{
    public function __construct(
        protected readonly Application $app,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('show', null, InputOption::VALUE_NONE, 'Affiche la clé sans modifier .env');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = 'base64:' . base64_encode(random_bytes(32));

        if ((bool) $input->getOption('show')) {
            $output->writeln("<comment>{$key}</comment>");
            return Command::SUCCESS;
        }

        $envPath = $this->app->basePath('.env');
        if (!file_exists($envPath)) {
            $examplePath = $this->app->basePath('.env.example');
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                file_put_contents($envPath, "APP_KEY=\n");
            }
        }

        $content = file_get_contents($envPath);
        if ($content === false) {
            $output->writeln('<error>Impossible de lire le fichier .env.</error>');
            return Command::FAILURE;
        }

        if (preg_match('/^APP_KEY=.*$/m', $content) === 1) {
            $updated = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $content, 1);
        } else {
            $updated = rtrim($content, "\r\n") . "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envPath, $updated);
        $output->writeln("<info>Clé applicative générée avec succès : {$key}</info>");

        return Command::SUCCESS;
    }
}
