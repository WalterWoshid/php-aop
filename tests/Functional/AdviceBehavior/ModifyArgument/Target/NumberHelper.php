<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\ModifyArgument\Target;

class NumberHelper
{
    /** @param array<array-key, int> $numbers */
    public function sumArray(array $numbers): int
    {
        return array_sum($numbers);
    }
}
