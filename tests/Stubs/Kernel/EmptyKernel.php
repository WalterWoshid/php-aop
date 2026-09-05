<?php

namespace Okapi\Aop\Tests\Stubs\Kernel;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Util;

class EmptyKernel extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    /** @var array<array-key, class-string> */
    protected array $aspects = [];
}
