<?php

namespace Extro\Di;

use Closure;
use Extro\Di\Traits\ContainerTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

class Invoker
{
    use ContainerTrait;

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function invoke(array|Closure $action): mixed
    {
        if (is_array($action)) {
            if (
                isset($action[0])
                && isset($action[1])
            ) {
                return $this->executeClassMethod(class: $action[0], method: $action[1]);
            }

            throw new RuntimeException(
                sprintf(
                    'Incorrect array [%s] for resolving. Array must be [class, method]',
                    implode(', ', $action)
                )
            );
        }

        return $this->executeClosure($action);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function executeClosure(Closure $closure)
    {
        return $this->callClosure($closure);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws ReflectionException
     */
    protected function executeClassMethod(string $class, string $method)
    {
        $object = $this->container->get($class);

        if (!method_exists($object, $method)) {
            throw new RuntimeException(sprintf(
                "Method '%s' for class '%s' not found",
                $method,
                $class
            ));
        }

        $arguments = $this->prepareMethodArguments($class, $method);
        return call_user_func_array([$object, $method], $arguments);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function prepareMethodArguments(string $className, string $methodName): array
    {
        $reflection = new ReflectionMethod($className, $methodName);
        $params = $reflection->getParameters();

        return $this->prepareArguments($params);
    }
}
