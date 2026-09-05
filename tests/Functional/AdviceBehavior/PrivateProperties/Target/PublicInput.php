<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class PublicInput extends ParentInput
{
    public string $tokens = 'public child';
    private ?string $uninitialized;
    public function childTokens(): string { return $this->tokens; }
}
