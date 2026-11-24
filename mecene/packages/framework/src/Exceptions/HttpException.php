<?php

declare(strict_types=1);

namespace Mecene\Exceptions;

use RuntimeException;

/**
 * Exception HTTP portant un code de statut (404, 403, 419, 500...).
 */
class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message, $statusCode);
    }
}
