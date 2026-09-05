<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class StaticChild extends StaticParent
{
    private static string $tokens = 'child';

    public static function childTokens(): string
    {
        return self::$tokens;
    }
}
