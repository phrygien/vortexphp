<?php

declare(strict_types=1);

namespace Mecene\Container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception levée lorsqu'un service est introuvable dans le conteneur PSR-11.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
