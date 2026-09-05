<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class ParentInput
{
    private array $tokens = ['parent'];
    private array $unique = ['unique'];

    public function parentTokens(): array { return $this->tokens; }
    public function unique(): array { return $this->unique; }
}
