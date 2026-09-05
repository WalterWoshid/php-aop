<?php

namespace Okapi\Aop\Tests\Functional\ErrorHandling\InvalidAspect\Kernel;

use Okapi\Aop\AopKernel;

class InvalidAspectsTypeKernel extends AopKernel
{
    /** @var array<array-key, class-string> */
    protected array $aspects = [
        42,
    ];
}
