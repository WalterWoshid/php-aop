<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties;

use Okapi\Aop\Attributes\Aspect;
use Okapi\Aop\Attributes\Before;
use Okapi\Aop\Invocation\BeforeMethodInvocation;

#[Aspect]
class EverythingAspect
{
    public static int $calls = 0;
    public static BeforeMethodInvocation $invocation;

    #[Before(class: 'Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\*', method: '*')]
    public function before(BeforeMethodInvocation $invocation): void
    {
        self::$calls++;
        self::$invocation = $invocation;
    }
}
