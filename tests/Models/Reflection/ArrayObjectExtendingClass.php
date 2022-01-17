<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests\Models\Reflection;

use ArrayObject;

/**
 * A test asset extending {@see \ArrayObject}, useful for verifying internal classes issues with reflection
 */
class ArrayObjectExtendingClass extends ArrayObject
{
    /** @var mixed */
    private $privateProperty;

    /** @var mixed */
    protected $protectedProperty;

    /** @var mixed */
    public $publicProperty;
}
