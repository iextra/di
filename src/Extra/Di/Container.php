<?php

namespace Extra\Di;

use Psr\Container\ContainerInterface;

class Container implements \ArrayAccess, ContainerInterface
{
    private $definitions = [];
    private $cache = [];

    public function set($id, $value)
    {
        if(true === array_key_exists($id, $this->cache)){
            unset($this->cache[$id]);
        }

        $this->definitions[$id] = $value;
    }

    public function get($id)
    {
        if(true === array_key_exists($id, $this->cache)){
            return $this->cache[$id];
        }

        if(false === array_key_exists($id, $this->definitions)){

            $className = $id;
            if(true === class_exists($className)){
                return $this->cache[$id] = $this->createClass($className);
            }

            throw new ServiceNotFoundException('Undefined id "' . $id . '"');
        }

        $definition = $this->definitions[$id];

        if($definition instanceof \Closure){
            $this->cache[$id] = $definition($this);
        }
        else{
            $this->cache[$id] = $definition;
        }

        return $this->cache[$id];
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->definitions) || class_exists($id);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->definitions[$offset]);
        unset($this->cache[$offset]);
    }

    private function createClass($className)
    {
        $reflection = new \ReflectionClass($className);
        $arguments = $this->getConstructorArguments($reflection, $className);

        return $reflection->newInstanceArgs($arguments);
    }

    private function getConstructorArguments(\ReflectionClass $reflection, $className)
    {
        $arguments = [];

        if(($constructor = $reflection->getConstructor()) !== null){
            foreach ($constructor->getParameters() as $param){
                if($classParam = $param->getClass()){
                    $arguments[] = $this->get($classParam->getName());
                }
                else if($param->isArray()){
                    $arguments[] = [];
                }
                else{
                    if(!$param->isDefaultValueAvailable()){
                        $paramName = $param->getName();
                        $error = 'Failed to retrieve class name and the default value for param "' . $paramName . '"';
                        $error .= ' in service "' . $className . '"';

                        throw new \Exception($error);
                    }

                    $arguments[] = $param->getDefaultValue();
                }
            }
        }

        return $arguments;
    }
}