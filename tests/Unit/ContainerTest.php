<?php

declare(strict_types = 1);

namespace Selective\Tests\Container\Unit;

use Exception;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Selective\Container\Container;
use Selective\Container\Exceptions\ContainerException;
use Selective\Container\Exceptions\InvalidDefinitionException;
use Selective\Container\Exceptions\NotFoundException;
use Selective\Container\Resolver\ConstructorResolver;
use Selective\Tests\Container\Unit\Service\MyAbstractService;
use Selective\Tests\Container\Unit\Service\MyService;
use Selective\Tests\Container\Unit\Service\MyServiceA;
use Selective\Tests\Container\Unit\Service\MyServiceInvalid;
use stdClass;

/**
 * Test.
 *
 * @internal
 */
final class ContainerTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testConstruct(): void
    {
        $container = new Container([
            'id' => static function () {
                return new stdClass();
            },
        ]);

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);

        $factories = $reflectionFactories->getValue($container);

        self::assertCount(1, $factories);

        $factory = array_shift($factories);

        $service = $factory($container);

        self::assertInstanceOf(stdClass::class, $service);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testFactories(): void
    {
        $container = new Container();
        $container->factories([
            'id' => static function () {
                return new stdClass();
            },
        ]);

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);

        $factories = $reflectionFactories->getValue($container);

        self::assertCount(1, $factories);

        $factory = array_shift($factories);

        $service = $factory($container);

        self::assertInstanceOf(stdClass::class, $service);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testFactory(): void
    {
        $container = new Container();
        $container->factory('id', static function () {
            return new stdClass();
        });

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);

        $factories = $reflectionFactories->getValue($container);

        self::assertCount(1, $factories);

        $factory = array_shift($factories);

        $service = $factory($container);

        self::assertInstanceOf(stdClass::class, $service);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testReplace(): void
    {
        $this->expectException(LogicException::class);
        $this->expectErrorMessage('The factory cannot be modified');

        $container = new Container();

        $container->factory('id', static function () {
        });

        $container->factory('id', static function () {
        });
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testSet(): void
    {
        $container = new Container();
        $container->set('id', 'value');
        self::assertSame('value', $container->get('id'));

        $stdClass = new stdClass();
        $container->set(stdClass::class, $stdClass);
        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
        self::assertSame($stdClass, $container->get(stdClass::class));

        $container->set(stdClass::class, new stdClass());
        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
        self::assertNotSame($stdClass, $container->get(stdClass::class));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGet(): void
    {
        $factories = [];
        $factories['id'] = static function () {
            return new stdClass();
        };

        $container = new Container();

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);
        $reflectionFactories->setValue($container, $factories);

        $service = $container->get('id');

        self::assertInstanceOf(stdClass::class, $service);

        self::assertSame($service, $container->get('id'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetWithMissingId(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('There is no service with id "id"');
        $this->expectExceptionCode(0);

        $container = new Container();
        $container->get('id');
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testGetWithException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Could not create service with id "id"');
        $this->expectExceptionCode(0);

        $factories = [];
        $factories['id'] = static function (ContainerInterface $container): void {
            $container->get('unknown');
        };

        $container = new Container();

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);
        $reflectionFactories->setValue($container, $factories);

        $service = $container->get('id');

        self::assertInstanceOf(stdClass::class, $service);

        self::assertSame($service, $container->get('id'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testHas(): void
    {
        $container = new Container();

        self::assertFalse($container->has('id'));

        $factories = [];
        $factories['id'] = static function () {
            return new stdClass();
        };

        $reflectionFactories = new ReflectionProperty($container, 'factories');
        $reflectionFactories->setAccessible(true);
        $reflectionFactories->setValue($container, $factories);

        self::assertTrue($container->has('id'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithClass(): void
    {
        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $service = $container->get(MyService::class);

        self::assertInstanceOf(MyService::class, $service);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithClassA(): void
    {
        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $service = $container->get(MyServiceA::class);

        self::assertInstanceOf(MyServiceA::class, $service);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithInvalidClasses(): void
    {
        $this->expectException(InvalidDefinitionException::class);

        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $container->get(MyServiceInvalid::class);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithInvalidInternalClass(): void
    {
        $this->expectException(InvalidDefinitionException::class);

        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $container->get(Exception::class);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithInvalidInternalInterface(): void
    {
        $this->expectException(InvalidDefinitionException::class);

        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $container->get(NotFoundException::class);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithInvalidAbstractClass(): void
    {
        $this->expectException(InvalidDefinitionException::class);
        $this->expectErrorMessage('Entry "Selective\Tests\Container\Unit\Service\MyAbstractService" cannot ' .
            'be resolved: the class is not instantiable');

        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $container->get(MyAbstractService::class);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAutowireWithNotExistingClass(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectErrorMessage('There is no service with id "Nada\Foo"');

        $container = new Container();
        $container->addResolver(new ConstructorResolver($container));
        $container->get('Nada\Foo');
    }
}
