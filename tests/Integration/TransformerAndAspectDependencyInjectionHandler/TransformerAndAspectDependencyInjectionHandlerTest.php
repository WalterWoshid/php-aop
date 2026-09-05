<?php

namespace Okapi\Aop\Tests\Integration\TransformerAndAspectDependencyInjectionHandler;

use Okapi\Aop\Tests\ClassLoaderMockTrait;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class TransformerAndAspectDependencyInjectionHandlerTest extends TestCase
{
    use ClassLoaderMockTrait;

    /**
     * @see Transformer::transform()
     * @see Aspect::higherAnswer()
     */
    public function testTransformerAndAspectDependencyInjectionHandler(): void
    {
        Util::clearCache();

        ob_start();

        Kernel::init();

        $output = ob_get_clean();
        static::assertIsString($output);

        static::assertStringContainsString('Generating aspect instance: ' . Aspect::class, $output);
        static::assertStringContainsString('Generating transformer instance: ' . Transformer::class, $output);

        $this->assertWillBeWoven(Target::class);
        $class = new Target();

        static::assertSame(420.69, $class->answer());
    }
}
