<?php

declare(strict_types=1);

namespace DoctrineNullableEmbeddables\Tests;

class ClassWithTypedProperties
{
    public ClassWithTypedProperties $classWithTypedProperties;
    public ?ClassWithTypedProperties $nullableClassWithTypedProperties;
    public string $string;
    public ?string $nullableString;
}
