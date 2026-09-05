<?php

namespace Okapi\Aop\Tests\Integration\TransformerAndAspect;

use Okapi\Aop\Tests\ClassLoaderMockTrait;
use Okapi\Aop\Tests\Integration\TransformerAndAspect\Aspect\FixWrongReturnValueAspect;
use Okapi\Aop\Tests\Integration\TransformerAndAspect\Target\IncorrectFunctionAndReturnClass;
use Okapi\Aop\Tests\Integration\TransformerAndAspect\Transformer\FixIncorrectFunctionTransformer;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class TransformerAndAspectTest extends TestCase
{
    use ClassLoaderMockTrait;

    /**
     * @see FixIncorrectFunctionTransformer
     * @see FixWrongReturnValueAspect::fixWrongReturnValue()
     */
    public function testTransformerAndAspect(): void
    {
        Util::clearCache();
        Kernel::init();

        $this->assertWillBeWoven(IncorrectFunctionAndReturnClass::class);
        $class = new IncorrectFunctionAndReturnClass();
        static::assertTrue($class->checkIfFloat(1.0));
    }

    public function testCachedTransformerAndAspect(): void
    {
        Kernel::init();

        $this->assertAspectLoadedFromCache(IncorrectFunctionAndReturnClass::class);
        $class = new IncorrectFunctionAndReturnClass();
        static::assertTrue($class->checkIfFloat(42.0));
        static::assertFalse($class->checkIfFloat('Hello World!'));
    }
}
