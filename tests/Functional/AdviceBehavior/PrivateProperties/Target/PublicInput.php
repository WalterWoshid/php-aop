<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class PublicInput extends ParentInput
{
    public string $tokens = 'public child';
    /** @api */
    private ?string $uninitialized;

    public function hasUninitializedValue(): bool
    {
        return isset($this->uninitialized);
    }

    public function childTokens(): string
    {
        return $this->tokens;
    }
}
