<?php

declare(strict_types=1);

namespace Extro\Di;

use Closure;
use Extro\Di\Exception\ContainerException;
use Extro\Di\Exception\ContainerNotFoundException;
use Extro\Di\Exception\CouldNotResolveArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use RuntimeException;

class Container implements ContainerInterface
{
    protected const SINGLETON_INSTANCE_METHOD = 'getInstance';

    private array $aliases = [];

    private array $factories = [];

    private array $cache = [];

    private array $onAfterBuildHandles = [];

    /**
     * Returns an entry of the container by its name.
     *
     * @template T
     * @param  string|class-string<T> $id  Entry name or a class name.
     *
     * @return mixed|T
     *
     * @throws ContainerNotFoundException
     * @throws ContainerExceptionInterface
     */
    public function get(string $id)
    {
        $key = $this->getAlias($id);

        try {
            if (isset($this->cache[$key])) {
                return $this->cache[$key];
            }

            if (isset($this->factories[$key])) {
                $result = $this->callClosure($this->factories[$key]);
                return $this->cache[$key] = $this->onAfterBuild($result);
            }

            if (class_exists($key)) {
                $result = $this->createClass($key);
                return $this->cache[$key] = $this->onAfterBuild($result);
            }
        }
        catch (ReflectionException $exception) {
            throw new ContainerException($exception->getMessage(), 0, $exception);
        }

        throw new ContainerNotFoundException("Identifier '$key' not found in container");
    }

    public function has(string $id): bool
    {
        $key = $this->getAlias($id);
        return isset($this->cache[$key])
            || isset($this->factories[$key])
            || class_exists($key);
    }

    public function set(string $id, $value): static
    {
        $this->cache[$id] = $value;
        return $this;
    }

    public function setFactory(string $id, Closure $callback): static
    {
        $key = $this->getAlias($id);

        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }

        $this->factories[$key] = $callback;

        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    protected function createClass(string $class): object
    {
        if ($class === static::class) {
            return $this;
        }

        $arguments = [];
        $reflection = new ReflectionClass($class);

        if (($constructor = $reflection->getConstructor()) !== null) {

            if (!$constructor->isPublic()) {

                // Singleton
                if ($reflection->hasMethod(self::SINGLETON_INSTANCE_METHOD)) {
                    return call_user_func([$class, self::SINGLETON_INSTANCE_METHOD]);
                }

                throw new RuntimeException("No access to constructor of class '$class'");
            }

            try {
                $arguments = $this->prepareArguments($constructor->getParameters());
            } catch (CouldNotResolveArgumentException $exception) {
                throw new CouldNotResolveArgumentException(
                    message: sprintf(
                        "%s %s -> called in '%s'",
                        $exception->getMessage(),
                        PHP_EOL,
                        $class
                    ),
                    code: $exception->getCode(),
                    previous: $exception
                );
            }
        }

        return $reflection->newInstanceArgs($arguments);
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

    protected function getAlias(string $id): string
    {
        return $this->aliases[$id] ?? $id;
    }

    public function bind(string $alias, string $concrete): static
    {
        $this->aliases[$alias] = $concrete;
        return $this;
    }

    public function setOnAfterBuildHandler(Closure $handler): static
    {
        $this->onAfterBuildHandles[] = $handler;
        return $this;
    }

    protected function onAfterBuild($value)
    {
        foreach ($this->onAfterBuildHandles as $handler) {
            $value = $handler($value, $this);
        }

        return $value;
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
}
