<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target;

class SharedStaticChild extends SharedStaticParent
{
    public static int $value = 2;
}
