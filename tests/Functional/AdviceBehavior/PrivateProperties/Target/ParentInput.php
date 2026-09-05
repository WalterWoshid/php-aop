<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class ParentInput
{
    /** @var list<string> */
    private array $tokens = ['parent'];
    /** @var list<string> */
    private array $unique = ['unique'];

    /** @return list<string> */
    public function parentTokens(): array
    {
        return $this->tokens;
    }

    /** @return list<string> */
    public function unique(): array
    {
        return $this->unique;
    }
}
