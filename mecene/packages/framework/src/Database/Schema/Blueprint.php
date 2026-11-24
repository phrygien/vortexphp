<?php

declare(strict_types=1);

namespace Mecene\Database\Schema;

/**
 * Définition fluide des colonnes d'une table SQL pour les migrations.
 */
class Blueprint
{
    /** @var array<int, array<string, mixed>> */
    protected array $columns = [];

    /** @var string[] */
    protected array $foreignKeys = [];

    public function __construct(
        public readonly string $table,
        protected readonly string $driver = 'sqlite',
    ) {
    }

    public function id(string $column = 'id'): self
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'id',
            'nullable' => false,
            'unique' => false,
            'default' => null,
            'hasDefault' => false,
        ];
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        return $this->addColumn($column, 'string', ['length' => $length]);
    }

    public function text(string $column): self
    {
        return $this->addColumn($column, 'text');
    }

    public function integer(string $column): self
    {
        return $this->addColumn($column, 'integer');
    }

    public function boolean(string $column): self
    {
        return $this->addColumn($column, 'boolean');
    }

    public function timestamp(string $column): self
    {
        return $this->addColumn($column, 'timestamp');
    }

    public function foreignId(string $column, ?string $referencesTable = null, string $referencesColumn = 'id'): self
    {
        $this->addColumn($column, 'integer');

        if ($referencesTable !== null) {
            $this->foreignKeys[] = "FOREIGN KEY ({$column}) REFERENCES {$referencesTable}({$referencesColumn}) ON DELETE CASCADE";
        }

        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }

    public function nullable(): self
    {
        $lastIndex = array_key_last($this->columns);
        if ($lastIndex !== null) {
            $this->columns[$lastIndex]['nullable'] = true;
        }
        return $this;
    }

    public function unique(): self
    {
        $lastIndex = array_key_last($this->columns);
        if ($lastIndex !== null) {
            $this->columns[$lastIndex]['unique'] = true;
        }
        return $this;
    }

    public function default(mixed $value): self
    {
        $lastIndex = array_key_last($this->columns);
        if ($lastIndex !== null) {
            $this->columns[$lastIndex]['default'] = $value;
            $this->columns[$lastIndex]['hasDefault'] = true;
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function addColumn(string $name, string $type, array $extra = []): self
    {
        $this->columns[] = array_merge([
            'name' => $name,
            'type' => $type,
            'nullable' => false,
            'unique' => false,
            'default' => null,
            'hasDefault' => false,
        ], $extra);

        return $this;
    }

    public function toSql(): string
    {
        $lines = [];

        foreach ($this->columns as $col) {
            if ($col['type'] === 'id') {
                $lines[] = match ($this->driver) {
                    'mysql' => "{$col['name']} BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY",
                    'pgsql' => "{$col['name']} SERIAL PRIMARY KEY",
                    default => "{$col['name']} INTEGER PRIMARY KEY AUTOINCREMENT",
                };
                continue;
            }

            $sqlType = match ($col['type']) {
                'string' => 'VARCHAR(' . ($col['length'] ?? 255) . ')',
                'text' => 'TEXT',
                'integer' => 'INTEGER',
                'boolean' => $this->driver === 'pgsql' ? 'BOOLEAN' : 'TINYINT(1)',
                'timestamp' => $this->driver === 'sqlite' ? 'DATETIME' : 'TIMESTAMP',
                default => 'TEXT',
            };

            $def = "{$col['name']} {$sqlType}";
            $def .= $col['nullable'] ? ' NULL' : ' NOT NULL';

            if ($col['unique']) {
                $def .= ' UNIQUE';
            }

            if ($col['hasDefault']) {
                $val = $col['default'];
                $formatted = match (true) {
                    is_bool($val) => $val ? '1' : '0',
                    is_int($val), is_float($val) => (string) $val,
                    $val === null => 'NULL',
                    default => "'" . str_replace("'", "''", (string) $val) . "'",
                };
                $def .= " DEFAULT {$formatted}";
            }

            $lines[] = $def;
        }

        foreach ($this->foreignKeys as $fk) {
            $lines[] = $fk;
        }

        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (\n  %s\n)",
            $this->table,
            implode(",\n  ", $lines)
        );
    }
}
