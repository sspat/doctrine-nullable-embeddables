# Nullable embeddables for Doctrine

As soon as you start using embeddable value objects in your Doctrine entities there is chance
you will run in the problem, that Doctrine will instantiate value objects even when the
corressponding value in the database is null. This will lead to typing-related PHP errors.

Basic example:
```php
<?php

declare(strict_types=1);

class ValueObject
{
    private $value; // Doctrine will set this property to null on hydration

    public function __construct(string $value)
    {
        $this->value = $value;    
    }

    public function __toString() : string
    {
        return $this->value; // This will be null and throw an error
    }
}

``` 

If you want to use PHP 7.4 typed properties:
```php
<?php

declare(strict_types=1);

class ValueObject
{
    private string $value; // Doctrine will try to set this property to null on hydration and an error will be thrown

    public function __construct(string $value)
    {
        $this->value = $value;    
    }

    public function __toString() : string
    {
        return $this->value;
    }
}

``` 

This problem was discussed in the doctrine/orm repository issue:
https://github.com/doctrine/orm/issues/4568

The current consensus is that this feature will land in the 3.x versions only.

So this leaves you with the following options:

**Make your value objects properties nullable.**

This will break the entity and value objects invariants
and introduce a lot of unnecessary checks in your code:
```php
<?php

declare(strict_types=1);

class ValueObject
{
    private ?string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('ValueObject value cannot be an empty string');
        }

        $this->value = $value;    
    }

    public function __toString() : string
    {
        return (string) $this->value; // invariant broken, you will get an empty string     
    }
}

```

**Use a Doctrine lifecycle callback to reset the entity properties to null after hydration.**

You will still need to set the value object's properties as nullable to avoid errors during
hydration and your nullable value objects will need some logic, for example implement a special
interface, to allow the lifecycle callback to determine whether the hydrated value is null or not.

```php
<?php

declare(strict_types=1);

interface NullableValueObjectInterface
{
    public function isNull() : bool;
}

class ValueObject implements NullableValueObjectInterface
{
    private ?string $value;

    public function __construct(string $value)
    {
        $this->value = $value;    
    }

    public function isNull() : bool
    {
        return $this->value === null;
    }

    public function __toString() : string
    {
        return $this->value;
    }
}

```

You will also need to configure each nullable value object for each entity to avoid running
the lifecycle callback for all entity properties.

```php
<?php

use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use Tarifhaus\Doctrine\ORM\NullableEmbeddableListenerFactory;

$listener = NullableEmbeddableListenerFactory::createWithClosureNullator();
$listener->addMapping('App\Domain\User\Model\UserProfile', 'address');

$evm = new EventManager();
$evm->addEventListener([Events::postLoad], $listener);
```

If you choose this path, there is an implementation available: https://github.com/tarifhaus/doctrine-nullable-embeddable

**Fork Doctrine to implement your own hydration mechanism**

This is pretty straightforward and the implications will be in maintaining your own fork
and keeping up with the upstream changes.

**Override specific Doctrine classes with your own**

This is what this package is doing.

It will install doctrine/orm of a specific version. The version is always the same 
as of this package and is locked by it, so if you want to update Doctrine you
will need to update this package to the same version as the Doctrine version you want.
This locking is needed to ensure that there are no changes to the files this package is
patching.

After installing doctrine/orm, a patch will be applyed to the Doctrine class `Doctrine/ORM/Mapping/ReflectionEmbeddedProperty`
using https://github.com/cweagans/composer-patches.

You can review the contents of this patch in `patch/nullable_embeddables.patch`

It works by analyzing the types of the properties of the entity and the value-object.
If the entity property containing the value-object is declared as nullable and
none of the value-object's properties that are not declared nullable get null values from the
database - then the entity property will be hydrated with the value object.
All other cases are considered an invalid state based on the provided typing and will result in
hydrating to null on the corresponding property of the entity.

The tradeoffs of this approach will be:
- You can't update Doctrine directly, only update it with this package
- You will need PHP 7.4
- Your entities properties containing nullable value objects must be typed
- Your entities properties containing nullable value objects must be nullable
- Your nullable value objects properties must be typed

A working example would look like this:
```php
<?php

declare(strict_types=1);

class Entity
{
    private ?ValueObject $nullableValueObject;

    public function setValueObject(string $value) : void
    {
        $this->nullableValueObject = new ValueObject($value);
    }

    public function getValueObject() : ?ValueObject
    {
        return $this->nullableValueObject;
    }
}

class ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;    
    }

    public function __toString() : string
    {
        return $this->value;    
    }
}
```

If you choose this path, you can install this package in the following steps: 
- add to your composer.json:
```json
{
    "extra": {
        "enable-patching": true
    }
}
```
Then depending on the version of doctrine/orm you want to use:

- run `composer require sspat/doctrine-nullable-embeddables:v2.10.5 doctrine/orm:2.10.5`
- run `composer require sspat/doctrine-nullable-embeddables:v2.10.4 doctrine/orm:2.10.4`
- run `composer require sspat/doctrine-nullable-embeddables:v2.10.3 doctrine/orm:2.10.3`
- run `composer require sspat/doctrine-nullable-embeddables:v2.10.2 doctrine/orm:2.10.2`
- run `composer require sspat/doctrine-nullable-embeddables:v2.10.1 doctrine/orm:2.10.1`
- run `composer require sspat/doctrine-nullable-embeddables:v2.10.0 doctrine/orm:2.10.0`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.6 doctrine/orm:2.9.6`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.5 doctrine/orm:2.9.5`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.4 doctrine/orm:2.9.4`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.3 doctrine/orm:2.9.3`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.2 doctrine/orm:2.9.2`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.1 doctrine/orm:2.9.1`
- run `composer require sspat/doctrine-nullable-embeddables:v2.9.0 doctrine/orm:2.9.0`
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.5 doctrine/orm:2.8.5`
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.4 doctrine/orm:2.8.4`
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.3 doctrine/orm:2.8.3`
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.2 doctrine/orm:2.8.2` 
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.1 doctrine/orm:2.8.1`
- run `composer require sspat/doctrine-nullable-embeddables:v2.8.0 doctrine/orm:2.8.0`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.5 doctrine/orm:2.7.5`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.4 doctrine/orm:2.7.4`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.3 doctrine/orm:2.7.3`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.2 doctrine/orm:2.7.2`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.1 doctrine/orm:2.7.1`
- run `composer require sspat/doctrine-nullable-embeddables:v2.7.0 doctrine/orm:2.7.0`
