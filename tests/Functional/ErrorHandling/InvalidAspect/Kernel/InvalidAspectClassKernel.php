<?php

namespace Okapi\Aop\Tests\Functional\ErrorHandling\InvalidAspect\Kernel;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Functional\ErrorHandling\InvalidAspect\Aspect\InvalidAspect;

class InvalidAspectClassKernel extends AopKernel
{
    /** @var array<array-key, class-string> */
    protected array $aspects = [
        InvalidAspect::class,
    ];
}
