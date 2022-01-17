<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests;

use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Mapping\ReflectionEmbeddedProperty;
use DoctrineNullableEmbeddables\Tests\Models\Generic\BooleanModel;
use DoctrineNullableEmbeddables\Tests\Models\Reflection\AbstractEmbeddable;
use DoctrineNullableEmbeddables\Tests\Models\Reflection\ArrayObjectExtendingClass;
use DoctrineNullableEmbeddables\Tests\Models\Reflection\ConcreteEmbeddable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ReflectionEmbeddedPropertyTest extends TestCase
{
    /**
     * @dataProvider getTestedReflectionProperties
     */
    public function testCanSetAndGetEmbeddedProperty(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass
    ) : void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        $object = $instantiator->instantiate($parentProperty->getDeclaringClass()->getName());

        $embeddedPropertyReflection->setValue($object, 'newValue');

        $this->assertSame('newValue', $embeddedPropertyReflection->getValue($object));

        $embeddedPropertyReflection->setValue($object, 'changedValue');

        $this->assertSame('changedValue', $embeddedPropertyReflection->getValue($object));
    }

    public function testGetNullOnUnitializedParentProperty() : void
    {
        $this->assertNull(
            (new ReflectionEmbeddedProperty(
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableClassWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableString'),
                ClassWithTypedProperties::class
            ))->getValue(
                (new Instantiator())->instantiate(ClassWithTypedProperties::class)
            )
        );
    }

    public function testGetNullOnUnitializedChildProperty() : void
    {
        $parentProperty = $this->getReflectionProperty(
            ClassWithTypedProperties::class,
            'nullableClassWithTypedProperties'
        );

        $object = (new Instantiator())->instantiate(ClassWithTypedProperties::class);

        $parentProperty->setValue($object, (new Instantiator())->instantiate(ClassWithTypedProperties::class));

        $this->assertNull(
            (new ReflectionEmbeddedProperty(
                $parentProperty,
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableString'),
                ClassWithTypedProperties::class
            ))->getValue($object)
        );
    }

    /**
     * @dataProvider getTestedTypedReflectionProperties
     */
    public function testSetValueOnTypedProperties(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass,
        ?string $childPropertyValue,
        ?string $expectedPropertyValue
    ) : void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        $object = $instantiator->instantiate($embeddableClass);

        $embeddedPropertyReflection->setValue($object, $childPropertyValue);

        $this->assertSame($expectedPropertyValue, $embeddedPropertyReflection->getValue($object));
    }

    /**
     * @dataProvider getTestedReflectionProperties
     */
    public function testWillSkipReadingPropertiesFromNullEmbeddable(
        ReflectionProperty $parentProperty,
        ReflectionProperty $childProperty,
        string $embeddableClass
    ) : void {
        $embeddedPropertyReflection = new ReflectionEmbeddedProperty($parentProperty, $childProperty, $embeddableClass);

        $instantiator = new Instantiator();

        $this->assertNull($embeddedPropertyReflection->getValue(
            $instantiator->instantiate($parentProperty->getDeclaringClass()->getName())
        ));
    }

    /**
     * @return ReflectionProperty[][]|string[][]
     */
    public function getTestedReflectionProperties() : array
    {
        return [
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                BooleanModel::class,
            ],
            // reflection on embeddables that have properties defined in abstract ancestors:
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(AbstractEmbeddable::class, 'propertyInAbstractClass'),
                ConcreteEmbeddable::class,
            ],
            [
                $this->getReflectionProperty(BooleanModel::class, 'id'),
                $this->getReflectionProperty(ConcreteEmbeddable::class, 'propertyInConcreteClass'),
                ConcreteEmbeddable::class,
            ],
            // reflection on classes extending internal PHP classes:
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'privateProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'protectedProperty'),
                ArrayObjectExtendingClass::class,
            ],
            [
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                $this->getReflectionProperty(ArrayObjectExtendingClass::class, 'publicProperty'),
                ArrayObjectExtendingClass::class,
            ],
            // reflection of classes with typed properties:
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'classWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'string'),
                ClassWithTypedProperties::class,
            ],
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'classWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableString'),
                ClassWithTypedProperties::class,
            ],
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableClassWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'string'),
                ClassWithTypedProperties::class,
            ],
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableClassWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableString'),
                ClassWithTypedProperties::class,
            ],
        ];
    }

    /**
     * @return ReflectionProperty[][]|string[][]|null[][]
     */
    public function getTestedTypedReflectionProperties() : array
    {
        return [
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableClassWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'string'),
                ClassWithTypedProperties::class,
                'string',
                'string',
            ],
            [
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'nullableClassWithTypedProperties'),
                $this->getReflectionProperty(ClassWithTypedProperties::class, 'string'),
                ClassWithTypedProperties::class,
                null,
                null,
            ],
        ];
    }

    private function getReflectionProperty(string $className, string $propertyName) : ReflectionProperty
    {
        $reflectionProperty = new ReflectionProperty($className, $propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }
}
