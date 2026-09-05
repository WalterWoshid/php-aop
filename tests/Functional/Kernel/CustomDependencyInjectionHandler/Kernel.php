<?php

namespace Okapi\Aop\Tests\Functional\Kernel\CustomDependencyInjectionHandler;

use Closure;
use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Util;

class Kernel extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    protected function dependencyInjectionHandler(): ?Closure
    {
        return /** @param class-string $className */ static function (string $className) {
            echo 'Generating aspect/transformer instance: ' . $className . PHP_EOL;

            return (new \ReflectionClass($className))->newInstance();
        };
    }

    /** @var array<array-key, class-string> */
    protected array $aspects = [
        Aspect::class,
    ];
}
