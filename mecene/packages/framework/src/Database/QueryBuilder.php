<?php

declare(strict_types=1);

namespace Mecene\Database;

use InvalidArgumentException;

/**
 * Query Builder PDO fluent générant des requêtes préparées sécurisées.
 */
class QueryBuilder
{
    protected string $table = '';

    /** @var string[] */
    protected array $columns = ['*'];

    /** @var array<int, array{boolean: string, type: string, column: string, operator?: string, value?: mixed, values?: array<int, mixed>}> */
    protected array $wheres = [];

    /** @var array<int, array{type: string, table: string, first: string, operator: string, second: string}> */
    protected array $joins = [];

    /** @var array<int, array{column: string, direction: string}> */
    protected array $orders = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    /** @var array<string, mixed> */
    protected array $bindings = [];

    protected int $paramCounter = 0;

    public function __construct(
        protected readonly Connection $connection,
    ) {
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param string|string[] $columns
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->addWhere('AND', $column, $operatorOrValue, $value);
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        return $this->addWhere('OR', $column, $operatorOrValue, $value);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = [
                'boolean' => 'AND',
                'type' => 'raw',
                'column' => '1 = 0',
            ];
            return $this;
        }

        $this->wheres[] = [
            'boolean' => 'AND',
            'type' => 'in',
            'column' => $column,
            'values' => array_values($values),
        ];

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $dir = strtoupper($direction);
        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Direction ORDER BY invalide : [{$direction}].");
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $dir,
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $this->bindings = [];
        $this->paramCounter = 0;

        $sql = $this->compileSelect();
        $stmt = $this->connection->execute($sql, $this->bindings);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $this->bindings = [];
        $this->paramCounter = 0;

        $sql = 'SELECT COUNT(*) AS aggregate FROM ' . $this->table
            . $this->compileJoins()
            . $this->compileWheres();

        $stmt = $this->connection->execute($sql, $this->bindings);
        $row = $stmt->fetch();

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insert(array $values): string
    {
        if ($values === []) {
            throw new InvalidArgumentException('Impossible d\'insérer un tableau vide.');
        }

        $this->bindings = [];
        $this->paramCounter = 0;

        $columns = array_keys($values);
        $placeholders = [];

        foreach ($values as $value) {
            $placeholders[] = $this->nextParam($value);
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->connection->execute($sql, $this->bindings);

        return $this->connection->lastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        $this->bindings = [];
        $this->paramCounter = 0;

        $sets = [];
        foreach ($values as $column => $value) {
            $param = $this->nextParam($value);
            $sets[] = "{$column} = {$param}";
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->table,
            implode(', ', $sets),
            $this->compileWheres()
        );

        $stmt = $this->connection->execute($sql, $this->bindings);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $this->bindings = [];
        $this->paramCounter = 0;

        $sql = sprintf('DELETE FROM %s%s', $this->table, $this->compileWheres());
        $stmt = $this->connection->execute($sql, $this->bindings);

        return $stmt->rowCount();
    }

    protected function addWhere(string $boolean, string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null && !in_array((string) $operatorOrValue, ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE'], true)) {
            $operator = '=';
            $val = $operatorOrValue;
        } else {
            $operator = strtoupper((string) $operatorOrValue);
            $val = $value;
        }

        $this->wheres[] = [
            'boolean' => $boolean,
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $val,
        ];

        return $this;
    }

    protected function compileSelect(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ' . $this->table;
        $sql .= $this->compileJoins();
        $sql .= $this->compileWheres();

        if ($this->orders !== []) {
            $clauses = array_map(
                fn (array $o): string => "{$o['column']} {$o['direction']}",
                $this->orders
            );
            $sql .= ' ORDER BY ' . implode(', ', $clauses);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    protected function compileJoins(): string
    {
        if ($this->joins === []) {
            return '';
        }

        $parts = array_map(
            fn (array $j): string => " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}",
            $this->joins
        );

        return implode('', $parts);
    }

    protected function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : $where['boolean'] . ' ';

            if ($where['type'] === 'raw') {
                $clauses[] = $prefix . $where['column'];
                continue;
            }

            if ($where['type'] === 'in') {
                $placeholders = [];
                foreach ($where['values'] ?? [] as $val) {
                    $placeholders[] = $this->nextParam($val);
                }
                $clauses[] = $prefix . "{$where['column']} IN (" . implode(', ', $placeholders) . ')';
                continue;
            }

            if ($where['value'] === null) {
                $nullOp = ($where['operator'] ?? '=') === '=' ? 'IS NULL' : 'IS NOT NULL';
                $clauses[] = $prefix . "{$where['column']} {$nullOp}";
                continue;
            }

            $param = $this->nextParam($where['value']);
            $clauses[] = $prefix . "{$where['column']} {$where['operator']} {$param}";
        }

        return ' WHERE ' . implode(' ', $clauses);
    }

    protected function nextParam(mixed $value): string
    {
        $name = ':p' . (++$this->paramCounter);
        $this->bindings[$name] = $value;
        return $name;
    }
}
