<?php

/** @noinspection PhpUnused */
namespace Okapi\Aop\Tests\Integration\TransformerAndAspect\Aspect;

use Okapi\Aop\Attributes\After;
use Okapi\Aop\Attributes\Aspect;
use Okapi\Aop\Invocation\AfterMethodInvocation;
use Okapi\Aop\Tests\Integration\TransformerAndAspect\Target\IncorrectFunctionAndReturnClass;

#[Aspect]
class FixWrongReturnValueAspect
{
    #[After(IncorrectFunctionAndReturnClass::class, 'checkIfFloat')]
    public function fixWrongReturnValue(AfterMethodInvocation $invocation): void
    {
        /** @var bool $result */
        $result = $invocation->proceed();
        $invocation->setResult(!$result);
    }
}
