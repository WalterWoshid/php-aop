<?php

namespace Okapi\Aop\Tests\Integration\TransformerAndAspect\Target;

class IncorrectFunctionAndReturnClass
{
    public function checkIfFloat(mixed $value): bool
    {
        return !is_int($value);
    }
}
