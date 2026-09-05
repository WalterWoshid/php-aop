<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class PromotedInput extends ParentInput
{
    public function __construct(
        private string $tokens = 'promoted',
    ) {}

    public function childTokens(): string
    {
        return $this->tokens;
    }
}
