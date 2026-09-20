<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests;

use Doctrine\ORM\Mapping\PropertyAccessors\EmbeddablePropertyAccessor;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class EmbeddablePropertyAccessorTest extends TestCase
{
    private function accessor(
        string $parentClass,
        string $parentProperty,
        string $childClass,
        string $childProperty,
    ): EmbeddablePropertyAccessor {
        return new EmbeddablePropertyAccessor(
            PropertyAccessorFactory::createPropertyAccessor($parentClass, $parentProperty),
            PropertyAccessorFactory::createPropertyAccessor($childClass, $childProperty),
            $childClass,
        );
    }

    public function testCanSetAndGetEmbeddedProperty(): void
    {
        $accessor = $this->accessor(TestEntity::class, 'embeddable', TestEmbeddable::class, 'value');

        $entity = new TestEntity();

        $accessor->setValue($entity, 'foo');

        self::assertInstanceOf(TestEmbeddable::class, $entity->embeddable);
        self::assertSame('foo', $accessor->getValue($entity));

        $accessor->setValue($entity, 'bar');

        self::assertSame('bar', $accessor->getValue($entity));
    }

    public function testGetReturnsNullWhenParentEmbeddableIsNull(): void
    {
        $accessor = $this->accessor(TestEntity::class, 'embeddable', TestEmbeddable::class, 'value');

        self::assertNull($accessor->getValue(new TestEntity()));
    }

    public function testNullOnNonNullableChildNullsNullableParent(): void
    {
        $accessor = $this->accessor(
            EntityWithRequiredChild::class,
            'embeddable',
            RequiredValueEmbeddable::class,
            'value',
        );

        $entity                    = new EntityWithRequiredChild();
        $entity->embeddable        = new RequiredValueEmbeddable();
        $entity->embeddable->value = 'foo';

        $accessor->setValue($entity, null);

        self::assertNull($entity->embeddable);
        self::assertNull($accessor->getValue($entity));
    }

    public function testNullOnNullableChildKeepsEmbeddableAndSetsChildToNull(): void
    {
        $accessor = $this->accessor(TestEntity::class, 'embeddable', TestEmbeddable::class, 'value');

        $entity             = new TestEntity();
        $entity->embeddable = new TestEmbeddable();
        $entity->embeddable->value = 'foo';

        $accessor->setValue($entity, null);

        self::assertInstanceOf(TestEmbeddable::class, $entity->embeddable);
        self::assertNull($entity->embeddable->value);
    }

    public function testNullStillInitializesNonNullableEmbeddable(): void
    {
        $accessor = $this->accessor(NonNullableEntity::class, 'embeddable', TestEmbeddable::class, 'value');

        $entity = new NonNullableEntity();

        $accessor->setValue($entity, null);

        self::assertInstanceOf(TestEmbeddable::class, $entity->embeddable);
        self::assertNull($entity->embeddable->value);
    }

    public function testUntypedChildFallsBackToDefaultBehaviour(): void
    {
        $accessor = $this->accessor(EntityWithUntypedChild::class, 'embeddable', UntypedEmbeddable::class, 'value');

        $entity = new EntityWithUntypedChild();

        $accessor->setValue($entity, null);

        self::assertInstanceOf(UntypedEmbeddable::class, $entity->embeddable);
        self::assertNull($entity->embeddable->value);
    }

    public function testValidPartiallyNullEmbeddableIsPreservedOptionalChildFirst(): void
    {
        $country = $this->accessor(Customer::class, 'address', Address::class, 'country');
        $line2   = $this->accessor(Customer::class, 'address', Address::class, 'line2');

        $customer = new Customer();

        $country->setValue($customer, 'DE');
        $line2->setValue($customer, null);

        self::assertInstanceOf(Address::class, $customer->address);
        self::assertSame('DE', $customer->address->country);
        self::assertNull($customer->address->line2);
    }

    public function testValidPartiallyNullEmbeddableIsPreservedOptionalChildLast(): void
    {
        $country = $this->accessor(Customer::class, 'address', Address::class, 'country');
        $line2   = $this->accessor(Customer::class, 'address', Address::class, 'line2');

        $customer = new Customer();

        $line2->setValue($customer, null);
        $country->setValue($customer, 'DE');

        self::assertInstanceOf(Address::class, $customer->address);
        self::assertSame('DE', $customer->address->country);
        self::assertNull($customer->address->line2);
    }

    public function testNullRequiredChildClearsNullableParent(): void
    {
        $country = $this->accessor(Customer::class, 'address', Address::class, 'country');
        $line2   = $this->accessor(Customer::class, 'address', Address::class, 'line2');

        $customer = new Customer();

        $line2->setValue($customer, 'Somewhere');
        $country->setValue($customer, null);

        self::assertNull($customer->address);
    }

    /**
     * Out of scope: an embeddable with several required properties where the
     * database row contains an inconsistent (partial) value cannot be resolved
     * correctly by the per-field accessor. The result is order dependent. This
     * limitation predates the Doctrine ORM 3.x port and is intentionally not
     * fixed here (no nested embeddables and no multi-required-field embeddables
     * are used by the consuming projects). See README "Known limitations".
     */
    public function testMultipleRequiredFieldsPartialRowIsOrderDependentOutOfScope(): void
    {
        $a = $this->accessor(PairHolder::class, 'pair', Pair::class, 'a');
        $b = $this->accessor(PairHolder::class, 'pair', Pair::class, 'b');

        $holderRequiredLast = new PairHolder();
        $b->setValue($holderRequiredLast, 'B');
        $a->setValue($holderRequiredLast, null);

        self::assertNull(
            $holderRequiredLast->pair,
            'Required NULL processed last clears the parent.',
        );

        $holderRequiredFirst = new PairHolder();
        $a->setValue($holderRequiredFirst, null);
        $b->setValue($holderRequiredFirst, 'B');

        self::assertInstanceOf(
            Pair::class,
            $holderRequiredFirst->pair,
            'Required NULL processed first clears then recreates the parent, leaving it incomplete.',
        );
        self::assertSame('B', $holderRequiredFirst->pair->b);
        self::assertFalse(
            (new ReflectionProperty(Pair::class, 'a'))->isInitialized($holderRequiredFirst->pair),
            'The recreated object is left with an uninitialized required property.',
        );
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

class EntityWithRequiredChild
{
    public ?RequiredValueEmbeddable $embeddable = null;
}

class RequiredValueEmbeddable
{
    public string $value;
}

class EntityWithUntypedChild
{
    public ?UntypedEmbeddable $embeddable = null;
}

class UntypedEmbeddable
{
    public $value;
}

class Address
{
    public string $country;
    public ?string $line2;
}

class Customer
{
    public ?Address $address = null;
}

class Pair
{
    public string $a;
    public string $b;
}

class PairHolder
{
    public ?Pair $pair = null;
}
