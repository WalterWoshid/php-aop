<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\MagicConstants\Kernel;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Functional\AdviceBehavior\MagicConstants\Aspect\AspectOnTrait;
use Okapi\Aop\Tests\Util;

class KernelOnTrait extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    /** @var array<array-key, class-string> */
    protected array $aspects = [
        AspectOnTrait::class,
    ];
}
