<?php

declare(strict_types=1);

namespace Mecene\Installer;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'new', description: 'Crée une nouvelle application Mecene dans le répertoire indiqué')]
class NewCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Nom du dossier du nouveau projet')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installe la dernière branche de développement (dev-main)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim((string) $input->getArgument('name'));
        if ($name === '' || preg_match('/^[a-zA-Z0-9_.\-\/]+$/', $name) !== 1) {
            $output->writeln('<error>Nom de projet invalide.</error>');
            return Command::FAILURE;
        }

        $targetDir = getcwd() . DIRECTORY_SEPARATOR . $name;
        if (is_dir($targetDir) || file_exists($targetDir)) {
            $output->writeln("<error>Le répertoire [{$name}] existe déjà.</error>");
            return Command::FAILURE;
        }

        $composer = $this->findComposer();
        $stability = (bool) $input->getOption('dev') ? ' --stability=dev' : '';

        $output->writeln('');
        $output->writeln("<info>Création du projet Mecene dans [{$name}]...</info>");

        $createCmd = sprintf(
            '%s create-project mecene/app %s%s --remove-vcs',
            $composer,
            escapeshellarg($name),
            $stability
        );

        if ($this->runProcess($createCmd, getcwd() ?: '.', $output) !== 0) {
            $output->writeln('<error>Échec lors de l\'exécution de composer create-project.</error>');
            return Command::FAILURE;
        }

        // Initialisation d'un dépôt Git neuf dans le projet généré
        if ($this->hasGit()) {
            $output->writeln('<info>Initialisation du dépôt Git...</info>');
            $this->runProcess('git init -q', $targetDir, $output);
            $this->runProcess('git add .', $targetDir, $output);
            $this->runProcess('git commit -q -m "Initial commit"', $targetDir, $output);
        }

        $output->writeln('');
        $output->writeln('<info>Application prête ! Prochaines étapes :</info>');
        $output->writeln("  <comment>cd {$name}</comment>");
        $output->writeln('  <comment>php artisan migrate</comment>');
        $output->writeln('  <comment>php artisan serve</comment>');
        $output->writeln('');

        return Command::SUCCESS;
    }

    protected function findComposer(): string
    {
        $localPhar = getcwd() . '/composer.phar';
        if (file_exists($localPhar)) {
            return escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($localPhar);
        }

        return 'composer';
    }

    protected function hasGit(): bool
    {
        $exitCode = 1;
        exec('git --version 2>/dev/null', $ignored, $exitCode);
        return $exitCode === 0;
    }

    protected function runProcess(string $command, string $cwd, OutputInterface $output): int
    {
        $descriptors = [
            0 => STDIN,
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);
        if (!is_resource($process)) {
            return 1;
        }

        while (!feof($pipes[1])) {
            $line = fgets($pipes[1]);
            if ($line !== false) {
                $output->write($line);
            }
        }

        while (!feof($pipes[2])) {
            $err = fgets($pipes[2]);
            if ($err !== false) {
                $output->write($err);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }
}
