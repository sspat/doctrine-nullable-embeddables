<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests;

use Doctrine\ORM\Mapping\PropertyAccessors\EmbeddablePropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EmbeddablePropertyAccessorTest extends TestCase
{
    public function testCanSetAndGetEmbeddedProperty(): void
    {
        $parent = PropertyAccessorFactory::createPropertyAccessor(
            TestEntity::class,
            'embeddable'
        );

        $child = PropertyAccessorFactory::createPropertyAccessor(
            TestEmbeddable::class,
            'value'
        );

        $accessor = new EmbeddablePropertyAccessor(
            $parent,
            $child,
            TestEmbeddable::class
        );

        $entity = new TestEntity();

        $accessor->setValue($entity, 'foo');

        self::assertInstanceOf(TestEmbeddable::class, $entity->embeddable);
        self::assertSame('foo', $accessor->getValue($entity));

        $accessor->setValue($entity, 'bar');

        self::assertSame('bar', $accessor->getValue($entity));
    }

    public function testNullSetsNullableEmbeddableToNull(): void
    {
        $parent = PropertyAccessorFactory::createPropertyAccessor(
            TestEntity::class,
            'embeddable'
        );

        $child = PropertyAccessorFactory::createPropertyAccessor(
            TestEmbeddable::class,
            'value'
        );

        $accessor = new EmbeddablePropertyAccessor(
            $parent,
            $child,
            TestEmbeddable::class
        );

        $entity = new TestEntity();
        $entity->embeddable = new TestEmbeddable();
        $entity->embeddable->value = 'foo';

        $accessor->setValue($entity, null);

        self::assertNull($entity->embeddable);
        self::assertNull($accessor->getValue($entity));
    }

    public function testNullStillInitializesNonNullableEmbeddable(): void
    {
        $parent = PropertyAccessorFactory::createPropertyAccessor(
            NonNullableEntity::class,
            'embeddable'
        );

        $child = PropertyAccessorFactory::createPropertyAccessor(
            TestEmbeddable::class,
            'value'
        );

        $accessor = new EmbeddablePropertyAccessor(
            $parent,
            $child,
            TestEmbeddable::class
        );

        $entity = new NonNullableEntity();

        $accessor->setValue($entity, null);

        self::assertInstanceOf(TestEmbeddable::class, $entity->embeddable);
        self::assertNull($entity->embeddable->value);
    }
}

class TestEntity
{
    public ?TestEmbeddable $embeddable = null;
}

class NonNullableEntity
{
    public TestEmbeddable $embeddable;
}

class TestEmbeddable
{
    public ?string $value = null;
}
