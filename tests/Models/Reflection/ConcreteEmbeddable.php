<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests\Models\Reflection;

/**
 * A test asset used to check that embeddables support properties defined in abstract classes
 */
class ConcreteEmbeddable extends AbstractEmbeddable
{
    /** @var string */
    private $propertyInConcreteClass;
}
