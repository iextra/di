<?php

declare(strict_types=1);

namespace Extro\Di\Contracts;

use Closure;

interface ContainerConfigInterface
{
    /**
     * Get class aliases configuration
     *
     * @return array<string, string>
     * Array where both keys and values are strings (abstract => concrete)
     */
    public function getAliases(): array;

    /**
     * Get factories configuration
     *
     * @return array<string, Closure>
     * Array where keys are strings (class names)
     * and values are Closure factory functions
     */
    public function getFactories(): array;

    /**
     * Get after-build callback handlers
     *
     * @return array<Closure>
     * Array of Closure callbacks that will be executed after building an object
     */
    public function getOnAfterBuildHandles(): array;
}
