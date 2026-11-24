<?php

declare(strict_types=1);

namespace Mecene\Database\Schema;

use Closure;
use Mecene\Database\Connection;

/**
 * Façade statique du Schema Builder pour exécuter les opérations DDL des migrations.
 */
class Schema
{
    protected static ?Connection $connection = null;

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    protected static function connection(): Connection
    {
        return self::$connection ?? app(Connection::class);
    }

    /**
     * @param Closure(Blueprint): void $callback
     */
    public static function create(string $table, Closure $callback): void
    {
        $conn = self::connection();
        $blueprint = new Blueprint($table, $conn->getDriver());
        $callback($blueprint);

        $conn->execute($blueprint->toSql());
    }

    public static function dropIfExists(string $table): void
    {
        self::connection()->execute("DROP TABLE IF EXISTS {$table}");
    }

    public static function hasTable(string $table): bool
    {
        $conn = self::connection();
        $driver = $conn->getDriver();

        if ($driver === 'sqlite') {
            $stmt = $conn->execute(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
                [':name' => $table]
            );
            return $stmt->fetch() !== false;
        }

        $stmt = $conn->execute(
            'SELECT table_name FROM information_schema.tables WHERE table_name = :name',
            [':name' => $table]
        );
        return $stmt->fetch() !== false;
    }
}
