<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

/** @property mixed $example */
class MagicInput
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function __get(string $name): mixed
    {
        return $this->values[$name] ?? 'virtual';
    }

    public function __set(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->values[$name]);
    }
}
