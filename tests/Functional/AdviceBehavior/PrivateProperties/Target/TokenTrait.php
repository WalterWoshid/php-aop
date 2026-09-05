<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

trait TokenTrait
{
    private string $tokens = 'trait';

    public function childTokens(): string
    {
        return $this->tokens;
    }
}
