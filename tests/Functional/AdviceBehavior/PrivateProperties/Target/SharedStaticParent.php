<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class SharedStaticParent
{
    public static int $value = 1;

    public static function read(): int
    {
        return self::$value;
    }
}
