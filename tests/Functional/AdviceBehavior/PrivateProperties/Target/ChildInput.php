<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class ChildInput extends ParentInput
{
    private $tokens = 'child';

    public function childTokens(): mixed
    {
        return $this->tokens;
    }
}
