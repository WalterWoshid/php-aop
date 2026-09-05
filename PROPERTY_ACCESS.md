# Accessing private properties from advice

Private properties keep their original visibility and declaring scope when a class
is woven. This fixes [#6](https://github.com/okapi-web/php-aop/issues/6): a parent and
child may legally declare private properties with the same name and different
types. They also retain independent values when their types are identical.

## Migration

Advice that previously read or wrote a private property directly through
`$invocation->getSubject()` must use `Okapi\Aop\PropertyAccess` instead. This applies
to all private properties, including properties whose names are currently unique.
Public and protected properties retain their existing behavior. Method interception
is unchanged.

```php
use Okapi\Aop\PropertyAccess;

$subject = $invocation->getSubject();

// Before: $subject->data = ['updated'];
PropertyAccess::set($subject, 'data', ['updated'], DatabaseService::class);
$data = PropertyAccess::get($subject, 'data', DatabaseService::class);
```

Use the original class that declares the property, without `__AopProxied`. For a
property supplied by a trait, use the class that uses the trait. The scope argument
can be omitted if the name identifies one property in the object's hierarchy.
Duplicate independent declarations require an explicit scope:

```php
PropertyAccess::set($input, 'tokens', ['parent'], ArgvInput::class);
PropertyAccess::set($input, 'tokens', 'child', CompletionInput::class);
```

For static properties, pass an object or class name as the first argument:

```php
$value = PropertyAccess::get(Service::class, 'configuration', Service::class);
PropertyAccess::set(Service::class, 'configuration', $value, Service::class);
```

The API uses PHP reflection, preserves declared types, and bypasses user-defined
`__get`/`__set` methods. It returns values, not references; to change an array, read
it, modify the local array, then write it back. A missing property or an invalid
declaring scope throws `ReflectionException`. An ambiguous name or a class-name
argument for an instance property throws `LogicException`. Uninitialized typed
properties still throw `Error` on read.

## Why private declarations must remain private

A parent can be loaded before its descendants are known. Making even a currently
unique private property public can invalidate a child loaded later. Checking only
known collisions would make behavior depend on load order and cached code.
Generated magic accessors are also unsafe as a general compatibility layer: their
signatures can conflict with a descendant's own magic methods. Explicit access
avoids changing either inheritance contract.

Clear the configured AOP cache when upgrading so existing generated classes are
rebuilt. Deploy this change with the advice migration above.
