<?php

declare(strict_types=1);

namespace Mecene\Validation;

use Mecene\Database\Connection;

/**
 * Validateur déclaratif supportant required, string, integer, numeric, email, min, max, in, unique et confirmed.
 */
class Validator
{
    /** @var array<string, string[]> */
    protected array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     */
    public function __construct(
        protected readonly array $data,
        protected readonly array $rules,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string>> $rules
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(): array
    {
        $this->errors = [];
        $validated = [];

        foreach ($this->rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $this->applyRule($field, $value, trim($rule));
            }

            if (!isset($this->errors[$field]) && array_key_exists($field, $this->data)) {
                $validated[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        if ($this->errors !== []) {
            throw new ValidationException($this->errors, $this->data);
        }

        return $validated;
    }

    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException) {
            return true;
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    protected function applyRule(string $field, mixed $value, string $rule): void
    {
        if ($rule === '') {
            return;
        }

        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
        $isEmpty = $value === null || (is_string($value) && trim($value) === '') || $value === [];

        if ($name !== 'required' && $isEmpty) {
            return;
        }

        match ($name) {
            'required' => $isEmpty
                ? $this->addError($field, "Le champ {$field} est obligatoire.")
                : null,
            'string' => !is_string($value)
                ? $this->addError($field, "Le champ {$field} doit être une chaîne de caractères.")
                : null,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false
                ? $this->addError($field, "Le champ {$field} doit être un entier valide.")
                : null,
            'numeric' => !is_numeric($value)
                ? $this->addError($field, "Le champ {$field} doit être numérique.")
                : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? $this->addError($field, "Le champ {$field} doit être une adresse e-mail valide.")
                : null,
            'min' => $this->checkMin($field, $value, (int) $param),
            'max' => $this->checkMax($field, $value, (int) $param),
            'in' => !in_array((string) $value, explode(',', (string) $param), true)
                ? $this->addError($field, "La valeur sélectionnée pour {$field} est invalide.")
                : null,
            'confirmed' => ($value !== ($this->data[$field . '_confirmation'] ?? null))
                ? $this->addError($field, "La confirmation du champ {$field} ne correspond pas.")
                : null,
            'unique' => $this->checkUnique($field, $value, (string) $param),
            default => null,
        };
    }

    protected function checkMin(string $field, mixed $value, int $min): void
    {
        if (is_numeric($value) && !is_string($value)) {
            if ($value < $min) {
                $this->addError($field, "Le champ {$field} doit être supérieur ou égal à {$min}.");
            }
            return;
        }

        if (mb_strlen(trim((string) $value)) < $min) {
            $this->addError($field, "Le champ {$field} doit contenir au moins {$min} caractères.");
        }
    }

    protected function checkMax(string $field, mixed $value, int $max): void
    {
        if (is_numeric($value) && !is_string($value)) {
            if ($value > $max) {
                $this->addError($field, "Le champ {$field} ne doit pas dépasser {$max}.");
            }
            return;
        }

        if (mb_strlen(trim((string) $value)) > $max) {
            $this->addError($field, "Le champ {$field} ne doit pas dépasser {$max} caractères.");
        }
    }

    protected function checkUnique(string $field, mixed $value, string $param): void
    {
        $parts = explode(',', $param);
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? $field;
        $ignoreId = $parts[2] ?? null;

        if ($table === '') {
            return;
        }

        /** @var Connection $conn */
        $conn = app(Connection::class);
        $query = $conn->query()->table($table)->where($column, $value);

        if ($ignoreId !== null && $ignoreId !== '') {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->count() > 0) {
            $this->addError($field, "Cette valeur pour {$field} est déjà utilisée.");
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }
}
