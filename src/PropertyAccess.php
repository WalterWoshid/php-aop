<?php
namespace Okapi\Aop;

use LogicException;
use Closure;
use Error;
use Okapi\Aop\Core\Cache\CachePaths;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/** Access a property without changing its declaring scope or storage. */
final class PropertyAccess
{
    /**
     * Read a value, optionally selecting its original declaring class.
     *
     * @throws ReflectionException If the property or declaring scope does not exist.
     * @throws LogicException If the name is ambiguous or an instance is required.
     */
    public static function get(object|string $subject, string $name, ?string $declaringClass = null): mixed
    {
        $property = self::resolve($subject, $name, $declaringClass);
        if (!$property->isInitialized(is_object($subject) ? $subject : null)) {
            throw new Error('Property ' . $property->getDeclaringClass()->getName()
                . '::$' . $name . ' must not be accessed before initialization');
        }
        return $property->getValue(is_object($subject) ? $subject : null);
    }

    /**
     * Write a value using PHP's property type checks.
     *
     * @throws ReflectionException If the property or declaring scope does not exist.
     * @throws LogicException If the name is ambiguous or an instance is required.
     */
    public static function set(object|string $subject, string $name, mixed $value, ?string $declaringClass = null): void
    {
        $property = self::resolve($subject, $name, $declaringClass);
        $property->setValue(is_object($subject) ? $subject : null, $value);
    }

    /** @internal Support indirect writes through an invocation's property accessor. */
    public static function &reference(object|string $subject, string $name, ?string $declaringClass = null): mixed
    {
        $property = self::resolve($subject, $name, $declaringClass);
        // Do not initialize nullable properties or invoke __get after explicit unset.
        if (!$property->isInitialized(is_object($subject) ? $subject : null)) {
            throw new Error('Typed property ' . $property->getDeclaringClass()->getName()
                . '::$' . $name . ' must not be accessed before initialization');
        }
        $scope = $property->getDeclaringClass()->getName();
        $read = $property->isStatic()
            ? Closure::bind(static function &() use ($name) { return self::$$name; }, null, $scope)
            : Closure::bind(function &() use ($name) { return $this->$name; }, $subject, $scope);
        $value =& $read();
        return $value;
    }

    /** @internal */
    public static function isSet(object|string $subject, string $name, ?string $declaringClass = null): bool
    {
        try {
            $property = self::resolve($subject, $name, $declaringClass);
        } catch (ReflectionException) {
            return false;
        }
        $object = is_object($subject) ? $subject : null;
        return $property->isInitialized($object) && $property->getValue($object) !== null;
    }

    /** @internal */
    public static function remove(object|string $subject, string $name, ?string $declaringClass = null): void
    {
        try {
            $property = self::resolve($subject, $name, $declaringClass);
        } catch (ReflectionException) {
            return;
        }
        if ($property->isStatic()) {
            throw new Error("Cannot unset static property \$$name.");
        }
        if (!$property->isInitialized($subject)) {
            return;
        }
        $remove = Closure::bind(function () use ($name): void {
            unset($this->$name);
        }, $subject, $property->getDeclaringClass()->getName());
        $remove();
    }

    private static function resolve(object|string $subject, string $name, ?string $declaringClass = null): ReflectionProperty
    {
        $matches = [];
        $scope = $declaringClass === null ? null : ltrim($declaringClass, '\\');
        $class = new ReflectionClass($subject);
        do {
            $originalName = $class->getName();
            if (str_ends_with($originalName, CachePaths::PROXIED_SUFFIX)) {
                $originalName = substr($originalName, 0, -strlen(CachePaths::PROXIED_SUFFIX));
            }
            if ($scope !== null && strcasecmp($scope, $originalName) !== 0) {
                continue;
            }
            foreach ($class->getProperties() as $property) {
                if ($property->getName() !== $name || $property->getDeclaringClass()->getName() !== $class->getName()) {
                    continue;
                }
                // Non-private instance overrides share storage. Redeclared static
                // properties and private declarations each have independent slots.
                $independent = $property->isPrivate() || $property->isStatic();
                if (!$independent && isset($matches['inherited'])) {
                    continue;
                }
                $key = $independent ? $class->getName() : 'inherited';
                $matches[$key] = $property;
            }
        } while ($class = $class->getParentClass());

        if (!$matches) {
            throw new ReflectionException("Property \$$name does not exist in the requested scope.");
        }
        if (count($matches) > 1) {
            throw new LogicException("Property \$$name is ambiguous; pass its original declaring class to PropertyAccess::get()/set().");
        }
        $property = reset($matches);
        if (is_string($subject) && !$property->isStatic()) {
            throw new LogicException("An object is required to access instance property \$$name.");
        }
        return $property;
    }
}
