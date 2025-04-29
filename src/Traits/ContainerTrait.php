<?php

namespace Extro\Di\Traits;

use Closure;
use Extro\Di\Exception\ContainerNotFoundException;
use Extro\Di\Exception\CouldNotResolveArgumentException;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;

trait ContainerTrait
{
    /**
     * @throws ContainerExceptionInterface
     * @throws CouldNotResolveArgumentException
     */
    protected function prepareArguments(array $parameters): array
    {
        $arguments = [];

        /** @var ReflectionParameter $param */
        foreach ($parameters as $param) {

            $position = $param->getPosition();
            $parameterType = $param->getType();

            if ($parameterType === null) {

                if ($param->isDefaultValueAvailable()) {
                    $arguments[$position] = $param->getDefaultValue();
                    continue;
                }

                $this->throwCouldNotResolveArgumentException($param->getName());
            }

            $name = $parameterType->getName();

            try {
                $arguments[$position] = $this->get($name);
            } catch (ContainerNotFoundException) {

                if ($param->isDefaultValueAvailable()) {
                    $arguments[$position] = $param->getDefaultValue();
                    continue;
                }

                $this->throwCouldNotResolveArgumentException($param->getName());
            }
        }

        return $arguments;
    }

    /**
     * @throws CouldNotResolveArgumentException
     */
    protected function throwCouldNotResolveArgumentException(string $argumentName): void
    {
        throw new CouldNotResolveArgumentException(sprintf(
            "Could not resolve argument '%s'",
            $argumentName
        ));
    }

    /**
     * @template T
     * @param  Closure  $closure  (...mixed):T $closure
     * @return T
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    protected function callClosure(Closure $closure)
    {
        $parameters = (new ReflectionFunction($closure))->getParameters();
        return call_user_func_array($closure, $this->prepareArguments($parameters));
    }
}
