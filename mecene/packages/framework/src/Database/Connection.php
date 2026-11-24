<?php

declare(strict_types=1);

namespace Mecene\Database;

use PDO;
use PDOStatement;
use RuntimeException;
use Mecene\Config\Repository;

/**
 * Gestionnaire de connexion PDO multi-pilotes (SQLite, MySQL, PostgreSQL).
 */
class Connection
{
    protected ?PDO $pdo = null;

    protected string $driver;

    /** @var array<int, array{sql: string, bindings: array<string|int, mixed>, time_ms: float}> */
    protected array $queryLog = [];

    public function __construct(
        protected readonly Repository $config,
        protected readonly string $basePath,
    ) {
        $this->driver = (string) $this->config->get('database.default', 'sqlite');
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function pdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $connections = (array) $this->config->get('database.connections', []);
        $cfg = $connections[$this->driver] ?? throw new RuntimeException("Pilote DB [{$this->driver}] non configuré.");

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($this->driver === 'sqlite') {
            $dbPath = (string) ($cfg['database'] ?? 'storage/database.sqlite');
            if ($dbPath !== ':memory:' && !str_starts_with($dbPath, DIRECTORY_SEPARATOR)) {
                $dbPath = $this->basePath . DIRECTORY_SEPARATOR . $dbPath;
            }

            if ($dbPath !== ':memory:') {
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
            }

            $this->pdo = new PDO("sqlite:{$dbPath}", null, null, $options);
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            return $this->pdo;
        }

        if ($this->driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['host'] ?? '127.0.0.1',
                $cfg['port'] ?? '3306',
                $cfg['database'] ?? 'mecene',
                $cfg['charset'] ?? 'utf8mb4'
            );
            $this->pdo = new PDO($dsn, (string) ($cfg['username'] ?? 'root'), (string) ($cfg['password'] ?? ''), $options);
            return $this->pdo;
        }

        if ($this->driver === 'pgsql') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $cfg['host'] ?? '127.0.0.1',
                $cfg['port'] ?? '5432',
                $cfg['database'] ?? 'mecene'
            );
            $this->pdo = new PDO($dsn, (string) ($cfg['username'] ?? 'postgres'), (string) ($cfg['password'] ?? ''), $options);
            return $this->pdo;
        }

        throw new RuntimeException("Pilote PDO non supporté : [{$this->driver}].");
    }

    /**
     * @param array<string|int, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        $start = microtime(true);
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bindings);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $this->queryLog[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time_ms' => $elapsed,
        ];

        return $stmt;
    }

    public function lastInsertId(): string
    {
        return $this->pdo()->lastInsertId();
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * @return array<int, array{sql: string, bindings: array<string|int, mixed>, time_ms: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
}
