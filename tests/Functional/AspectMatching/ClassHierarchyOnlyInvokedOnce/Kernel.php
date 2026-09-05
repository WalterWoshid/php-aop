<?php

namespace Okapi\Aop\Tests\Functional\AspectMatching\ClassHierarchyOnlyInvokedOnce;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Util;

class Kernel extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    /** @var array<array-key, class-string> */
    protected array $aspects = [
        Aspect::class,
    ];
}
