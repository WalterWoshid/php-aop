<?php

namespace Okapi\Aop\MagoTests;

use Closure;
use Okapi\Aop\AopKernel;
use Okapi\Aop\Component\ComponentType;

class Valid extends AopKernel
{
    protected function dependencyInjectionHandler(): ?Closure
    {
        return /** @param class-string $name */ static fn(string $name, ComponentType $type): object => $type
            === ComponentType::ASPECT
                ? new ExampleAspect()
                : new ExampleTransformer();
    }
}
