<?php

namespace Okapi\Aop\Invocation;

use Okapi\Aop\PropertyAccess;

/**
 * A property view of the subject; never a replacement for the subject itself.
 *
 */
final class PropertyAccessor
{
    public function __construct(
        private readonly object|string $subject,
        private readonly ?string $declaringClass = null,
    ) {}

    public function &__get(string $name): mixed
    {
        return PropertyAccess::reference($this->subject, $name, $this->declaringClass);
    }

    public function __set(string $name, mixed $value): void
    {
        PropertyAccess::set($this->subject, $name, $value, $this->declaringClass);
    }

    public function __isset(string $name): bool
    {
        return PropertyAccess::isSet($this->subject, $name, $this->declaringClass);
    }

    public function __unset(string $name): void
    {
        PropertyAccess::remove($this->subject, $name, $this->declaringClass);
    }
}
