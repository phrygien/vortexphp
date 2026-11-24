<?php

declare(strict_types=1);

namespace Mecene\Database\Migrations;

use Mecene\Database\Connection;
use Mecene\Database\Schema\Blueprint;
use Mecene\Database\Schema\Schema;

/**
 * Exécuteur de migrations avec suivi des lots (batches) et rollback.
 */
class Migrator
{
    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $migrationsPath,
    ) {
        Schema::setConnection($this->connection);
    }

    public function ensureRepositoryExists(): void
    {
        if (!Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table): void {
                $table->id();
                $table->string('migration')->unique();
                $table->integer('batch');
                $table->timestamp('migrated_at')->nullable();
            });
        }
    }

    /**
     * @return string[]
     */
    public function run(): array
    {
        $this->ensureRepositoryExists();

        $ran = $this->getRanMigrations();
        $files = $this->getMigrationFiles();
        $pending = array_filter(
            $files,
            fn (string $file): bool => !in_array(basename($file, '.php'), $ran, true)
        );

        if ($pending === []) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($pending as $file) {
            $name = basename($file, '.php');
            /** @var Migration $migration */
            $migration = require $file;
            $migration->up();

            $this->connection->query()->table('migrations')->insert([
                'migration' => $name,
                'batch' => $batch,
                'migrated_at' => date('Y-m-d H:i:s'),
            ]);

            $executed[] = $name;
        }

        return $executed;
    }

    /**
     * @return string[]
     */
    public function rollback(): array
    {
        $this->ensureRepositoryExists();

        $lastBatch = $this->getLastBatchNumber();
        if ($lastBatch === 0) {
            return [];
        }

        $rows = $this->connection->query()
            ->table('migrations')
            ->where('batch', $lastBatch)
            ->orderBy('id', 'DESC')
            ->get();

        $rolledBack = [];

        foreach ($rows as $row) {
            $name = (string) $row['migration'];
            $file = $this->migrationsPath . DIRECTORY_SEPARATOR . $name . '.php';

            if (file_exists($file)) {
                /** @var Migration $migration */
                $migration = require $file;
                $migration->down();
            }

            $this->connection->query()
                ->table('migrations')
                ->where('id', $row['id'])
                ->delete();

            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * @return string[]
     */
    protected function getRanMigrations(): array
    {
        $rows = $this->connection->query()->table('migrations')->select(['migration'])->get();
        return array_map(fn (array $r): string => (string) $r['migration'], $rows);
    }

    /**
     * @return string[]
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);
        return $files;
    }

    protected function getLastBatchNumber(): int
    {
        $row = $this->connection->query()
            ->table('migrations')
            ->select(['batch'])
            ->orderBy('batch', 'DESC')
            ->first();

        return (int) ($row['batch'] ?? 0);
    }

    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }
}
