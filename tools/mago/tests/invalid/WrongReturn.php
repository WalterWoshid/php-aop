<?php

namespace Okapi\Aop\MagoTests;

use Closure;
use Okapi\Aop\AopKernel;
use Okapi\Aop\Component\ComponentType;

class WrongReturn extends AopKernel
{
    protected function dependencyInjectionHandler(): ?Closure
    {
        return /** @param class-string $name */ static function (string $name, ComponentType $type): string {
            return 'invalid';
        };
    }
}
