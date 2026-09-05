<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class SameTypeInput extends ParentInput
{
    private array $tokens = ['child'];
    public function childTokens(): array { return $this->tokens; }
}
