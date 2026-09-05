# Accessing private properties from advice

Private properties keep their original visibility and declaring scope when a class
is woven. This fixes [#6](https://github.com/okapi-web/php-aop/issues/6): a parent and
child may legally declare private properties with the same name and different
types. They also retain independent values when their types are identical.

## Migration

Advice that previously read or wrote a private property directly through
`$invocation->getSubject()` should use `$invocation->properties()` instead. This applies
to all private properties, including properties whose names are currently unique.
Public and protected properties retain their existing behavior. Method interception
is unchanged.

```php
// Before: $subject->data = ['updated'];
$invocation->properties()->data = ['updated'];
$data = $invocation->properties()->data;
```

Use the original class that declares the property, without `__AopProxied`. For a
property supplied by a trait, use the class that uses the trait. The scope argument
can be omitted if the name identifies one property in the object's hierarchy.
Duplicate independent declarations require an explicit scope:

```php
$invocation->properties(ArgvInput::class)->tokens = ['parent'];
$invocation->properties(CompletionInput::class)->tokens = 'child';
```

The accessor supports array mutation, references, `isset`, and `unset`:

```php
$properties = $invocation->properties();
$properties->data[] = 'appended';
$reference =& $properties->data;
isset($properties->data);
unset($properties->data);
```

`properties()` is a view of the existing subject, not a replacement for it.
`getSubject()` still returns the same object. Subject type identity, internal method
calls, and method interception are unchanged. In static advice, the accessor uses
the invocation's class; instance properties require an object.

The lower-level `PropertyAccess` API is also available outside an invocation. For
static properties, pass an object or class name as the first argument:

```php
use Okapi\Aop\PropertyAccess;

$value = PropertyAccess::get(Service::class, 'configuration', Service::class);
PropertyAccess::set(Service::class, 'configuration', $value, Service::class);
```

Property access uses PHP reflection and closures bound to the declaring scope,
preserves declared types, and bypasses user-defined `__get`/`__set` methods.
The lower-level `get()` returns a value; the invocation accessor supports references.
A missing property or an invalid
declaring scope throws `ReflectionException`. An ambiguous name or a class-name
argument for an instance property throws `LogicException`. Uninitialized typed
properties still throw `Error` on read without initializing them. `isset` returns
false and `unset` does nothing for absent properties; ambiguous names still throw.
Static properties cannot be unset. Writes must name a declared property; the
accessor does not create dynamic properties. PHP also prevents taking references
to readonly properties on subjects whose readonly declarations remain intact.

## Why private declarations must remain private

A parent can be loaded before its descendants are known. Making even a currently
unique private property public can invalidate a child loaded later. Checking only
known collisions would make behavior depend on load order and cached code.
Generated magic accessors are also unsafe as a general compatibility layer: their
signatures can conflict with a descendant's own magic methods. Explicit access
avoids changing either inheritance contract.

Clear the configured AOP cache when upgrading so existing generated classes are
rebuilt. Deploy this change with the advice migration above.
