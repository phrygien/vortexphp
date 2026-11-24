<?php

declare(strict_types=1);

namespace Mecene\Config;

/**
 * Dépôt de configuration supportant la notation par points (ex: "database.default").
 */
class Repository
{
    /** @var array<string, mixed> */
    protected array $items = [];

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function loadFromDirectory(string $directory): self
    {
        $items = [];

        if (!is_dir($directory)) {
            return new self($items);
        }

        $files = glob($directory . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $data = require $file;
            if (is_array($data)) {
                $items[$key] = $data;
            }
        }

        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $segments = explode('.', $key);
        $current = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$this->items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current[array_shift($segments)] = $value;
    }

    public function has(string $key): bool
    {
        $sentinel = new \stdClass();
        return $this->get($key, $sentinel) !== $sentinel;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
