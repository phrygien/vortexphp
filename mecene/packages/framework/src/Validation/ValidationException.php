<?php

declare(strict_types=1);

namespace Mecene\Validation;

use Exception;

/**
 * Exception levée lorsqu'une validation échoue.
 */
class ValidationException extends Exception
{
    /**
     * @param array<string, string[]> $errors
     * @param array<string, mixed> $input
     */
    public function __construct(
        public readonly array $errors,
        public readonly array $input = [],
        string $message = 'Les données fournies sont invalides.',
    ) {
        parent::__construct($message, 422);
    }
}
