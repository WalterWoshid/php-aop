<?php

namespace Okapi\Aop\MagoTests;

use Closure;
use Okapi\Aop\AopKernel;

class ScalarCallback extends AopKernel
{
    protected function dependencyInjectionHandler(): ?Closure
    {
        return 42;
    }
}
