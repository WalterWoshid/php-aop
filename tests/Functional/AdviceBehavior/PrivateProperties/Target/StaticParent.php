<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class StaticParent
{
    /** @var list<string> */
    private static array $tokens = ['parent'];

    /** @return list<string> */
    public static function parentTokens(): array
    {
        return self::$tokens;
    }
}
