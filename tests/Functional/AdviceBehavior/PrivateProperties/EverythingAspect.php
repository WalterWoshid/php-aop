<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties;

use Okapi\Aop\Attributes\Aspect;
use Okapi\Aop\Attributes\Before;

#[Aspect]
class EverythingAspect
{
    public static int $calls = 0;

    #[Before(class: 'Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\*', method: '*')]
    public function before(): void
    {
        self::$calls++;
    }
}
