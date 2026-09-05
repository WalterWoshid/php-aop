<?php

namespace Okapi\Aop\Tests\Performance\Kernel;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Util;
use Okapi\CodeTransformer\Core\Options\Environment;

class MeasurePerformanceKernel extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    protected Environment $environment = Environment::DEVELOPMENT;

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /** @var array<array-key, class-string> */
    protected array $aspects = [
        \Okapi\Aop\Tests\Performance\Aspect\AddOneAspect::class,
    ];
}
