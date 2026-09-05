<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class StaticParent
{
    private static array $tokens = ['parent'];
    public static function parentTokens(): array { return self::$tokens; }
}
