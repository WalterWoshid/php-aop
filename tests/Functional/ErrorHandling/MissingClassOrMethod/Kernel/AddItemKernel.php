<?php

namespace Okapi\Aop\Tests\Functional\ErrorHandling\MissingClassOrMethod\Kernel;

use Okapi\Aop\AopKernel;
use Okapi\Aop\Tests\Functional\ErrorHandling\MissingClassOrMethod\Aspect\AddItemLoggerAspect;
use Okapi\Aop\Tests\Util;

class AddItemKernel extends AopKernel
{
    protected ?string $cacheDir = Util::CACHE_DIR;

    /** @var array<array-key, class-string> */
    protected array $aspects = [
        AddItemLoggerAspect::class,
    ];
}
