<?php

declare(strict_types=1);

namespace Extro\Di\Exception;

use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundException
    extends ContainerException
    implements NotFoundExceptionInterface
{
}