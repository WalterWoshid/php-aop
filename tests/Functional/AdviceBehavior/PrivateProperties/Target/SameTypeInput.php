<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class SameTypeInput extends ParentInput
{
    /** @var list<string> */
    private array $tokens = ['child'];

    /** @return list<string> */
    public function childTokens(): array
    {
        return $this->tokens;
    }
}
