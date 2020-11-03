<?php

namespace Tests\Extra\Di;

use Extra\Di\Container;
use Extra\Di\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testPrimitives()
    {
        $container = new Container();

        $container->set($key = 'str', $value = 'string');
        self::assertEquals($value, $container->get($key));

        $container->set($key = 'int', $value = 86);
        self::assertEquals($value, $container->get($key));

        $container->set($key = 'bool', $value = false);
        self::assertEquals($value, $container->get($key));

        $container->set($key = 'array', $value = ['array']);
        self::assertEquals($value, $container->get($key));

        $container->set($key = 'class', $value = new \stdClass());
        self::assertEquals($value, $container->get($key));
    }

    public function testNotFound()
    {
        $container = new Container();

        $this->expectException(ServiceNotFoundException::class);

        $container->get('not_existent_key');
    }

    public function testCallback()
    {
        $container = new Container();

        $container->set($key = 'callback', function (){
            return new \stdClass();
        });

        self::assertNotNull($value = $container->get($key));
        self::assertInstanceOf(\stdClass::class, $value);
    }

    public function testSingleton()
    {
        $container = new Container();

        $container->set($key = 'singleton', function (){
            return new \stdClass();
        });

        self::assertNotNull($value1 = $container->get($key));
        self::assertNotNull($value2 = $container->get($key));
        self::assertSame($value1, $value2);
    }

    public function testContainerPass()
    {
        $container = new Container();

        $container->set('param', $value = 'extra');
        $container->set($name = 'name', function (Container $container){
            $object = new \stdClass();
            $object->param = $container->get('param');
            return $object;
        });

        self::assertObjectHasAttribute('param', $object = $container->get($name));
        self::assertEquals($value, $object->param);
    }

    public function testAutoInstantiating()
    {
        $container = new Container();

        self::assertNotNull($value1 = $container->get(\stdClass::class));
        self::assertNotNull($value2 = $container->get(\stdClass::class));

        self::assertInstanceOf(\stdClass::class, $value1);
        self::assertInstanceOf(\stdClass::class, $value2);

        self::assertSame($value1, $value2);
    }

    public function testAutowiring()
    {
        $container = new Container();

        self::assertNotNull($otherClass = $container->get(Other::class));
        self::assertInstanceOf(Other::class, $otherClass);

        self::assertNotNull($middleClass = $otherClass->middleClass);
        self::assertInstanceOf(Middle::class, $middleClass);

        self::assertNotNull($innerClass = $middleClass->innerClass);
        self::assertInstanceOf(Inner::class, $innerClass);
    }

    public function testAutowiringScalarWithDefault()
    {
        $container = new Container();

        self::assertNotNull($scalarClass = $container->get(ScalarWithArrayAndDefault::class));
        self::assertInstanceOf(ScalarWithArrayAndDefault::class, $scalarClass);

        self::assertEquals(10, $scalarClass->default);
        self::assertEquals([], $scalarClass->array);
        self::assertInstanceOf(Inner::class, $scalarClass->innerClass);
    }
}

class Other
{
    public $middleClass;

    public function __construct(Middle $middleClass)
    {
        $this->middleClass = $middleClass;
    }
}

class Middle
{
    public $innerClass;

    public function __construct(Inner $innerClass)
    {
        $this->innerClass = $innerClass;
    }
}

class Inner
{

}

class ScalarWithArrayAndDefault
{
    public $innerClass;
    public $array;
    public $default;

    public function __construct(Inner $innerClass, array $array, $default = 10)
    {
        $this->innerClass = $innerClass;
        $this->array = $array;
        $this->default = $default;
    }
}