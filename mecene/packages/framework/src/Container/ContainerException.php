<?php

declare(strict_types=1);

namespace Mecene\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Exception levée lorsqu'une erreur survient lors de la résolution d'une dépendance.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
