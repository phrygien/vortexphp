<?php

declare(strict_types=1);

namespace Mecene\Database\ORM;

use ReflectionClass;
use Mecene\Database\Connection;
use Mecene\Database\QueryBuilder;
use Mecene\Exceptions\HttpException;

/**
 * ORM ActiveRecord type Eloquent : fillable, timestamps, relations hasMany / belongsTo.
 */
abstract class Model
{
    protected static ?Connection $connection = null;

    protected static string $table = '';

    protected static string $primaryKey = 'id';

    /** @var string[] */
    protected array $fillable = [];

    protected bool $timestamps = true;

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    protected array $relations = [];

    public bool $exists = false;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    protected static function connection(): Connection
    {
        return self::$connection ?? app(Connection::class);
    }

    public static function getTable(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        $shortName = (new ReflectionClass(static::class))->getShortName();
        return strtolower($shortName) . 's';
    }

    public static function query(): QueryBuilder
    {
        return static::connection()->query()->table(static::getTable());
    }

    /**
     * @return array<int, static>
     */
    public static function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $rows = static::query()->orderBy($orderBy, $direction)->get();
        return array_map(fn (array $row): static => static::hydrate($row), $rows);
    }

    public static function find(int|string $id): ?static
    {
        $row = static::query()->where(static::$primaryKey, $id)->first();
        return $row !== null ? static::hydrate($row) : null;
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new HttpException(404, 'Ressource introuvable.');
        }
        return $model;
    }

    /**
     * @return array<int, static>
     */
    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): array
    {
        $rows = static::query()->where($column, $operatorOrValue, $value)->get();
        return array_map(fn (array $row): static => static::hydrate($row), $rows);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->fillable === [] || in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        if ($this->exists) {
            if ($this->timestamps) {
                $this->attributes['updated_at'] = $now;
            }

            $pk = static::$primaryKey;
            $data = $this->attributes;
            $id = $data[$pk];
            unset($data[$pk]);

            static::query()->where($pk, $id)->update($data);
            return true;
        }

        if ($this->timestamps) {
            $this->attributes['created_at'] ??= $now;
            $this->attributes['updated_at'] ??= $now;
        }

        $id = static::query()->insert($this->attributes);
        $this->attributes[static::$primaryKey] = is_numeric($id) ? (int) $id : $id;
        $this->exists = true;

        return true;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $pk = static::$primaryKey;
        $affected = static::query()->where($pk, $this->attributes[$pk])->delete();
        $this->exists = false;

        return $affected > 0;
    }

    /**
     * @param class-string<Model> $related
     * @return array<int, Model>
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $shortName = strtolower((new ReflectionClass(static::class))->getShortName());
        $foreignKey ??= $shortName . '_id';
        $localKey ??= static::$primaryKey;

        return $related::where($foreignKey, $this->attributes[$localKey] ?? null);
    }

    /**
     * @param class-string<Model> $related
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): ?Model
    {
        $relatedShort = strtolower((new ReflectionClass($related))->getShortName());
        $foreignKey ??= $relatedShort . '_id';
        $ownerKey ??= 'id';

        $fkValue = $this->attributes[$foreignKey] ?? null;
        if ($fkValue === null) {
            return null;
        }

        $rows = $related::where($ownerKey, $fkValue);
        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->attributes = $row;
        $instance->exists = true;
        return $instance;
    }

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->relations[$key] = $this->{$key}();
        }

        return null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
