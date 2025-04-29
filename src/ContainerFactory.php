<?php

declare(strict_types=1);

namespace Extro\Di;

use Extro\Di\Contracts\ContainerConfigInterface;

readonly class ContainerFactory
{
    public static function create(ContainerConfigInterface $config): Container
    {
        $container = new Container();

        foreach ($config->getAliases() as $alias => $concrete) {
            $container->bind($alias, $concrete);
        }

        foreach ($config->getFactories() as $id => $closure) {
            $container->setFactory($id, $closure);
        }

        foreach ($config->getOnAfterBuildHandles() as $handler) {
            $container->setOnAfterBuildHandler($handler);
        }

        return $container;
    }
}
