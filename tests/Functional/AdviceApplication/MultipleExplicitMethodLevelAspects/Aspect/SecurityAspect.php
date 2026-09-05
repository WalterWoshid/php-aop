<?php

namespace Okapi\Aop\Tests\Functional\AdviceApplication\MultipleExplicitMethodLevelAspects\Aspect;

use Attribute;
use Okapi\Aop\Attributes\Aspect;
use Okapi\Aop\Attributes\Before;
use Okapi\Aop\Invocation\BeforeMethodInvocation;

#[Attribute]
#[Aspect]
class SecurityAspect
{
    public const SECRET_HASH = '-secret-hash';

    #[Before]
    public function applySecurityMeasures(BeforeMethodInvocation $invocation): void
    {
        /** @var non-empty-array<string, array{id: string}|string> $arguments */
        $arguments = $invocation->getArguments();

        $firstArgumentKey = array_key_first($arguments);
        $firstArgument = $arguments[$firstArgumentKey];

        if (is_array($firstArgument)) {
            $id = &$firstArgument['id'];
            $id .= self::SECRET_HASH;

            $arguments[$firstArgumentKey] = $firstArgument;

            $invocation->setArguments($arguments);
        }

        if (is_string($firstArgument)) {
            $id = &$firstArgument;
            $id .= self::SECRET_HASH;

            $arguments[$firstArgumentKey] = $id;

            $invocation->setArguments($arguments);
        }
    }
}
