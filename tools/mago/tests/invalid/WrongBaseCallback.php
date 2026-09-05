<?php

namespace Okapi\Aop\MagoTests;

use Closure;
use Okapi\CodeTransformer\CodeTransformerKernel;

class WrongBaseCallback extends CodeTransformerKernel
{
    protected function dependencyInjectionHandler(): ?Closure
    {
        return /** @param class-string $name */ static function (string $name): object {
            return new \stdClass();
        };
    }
}
