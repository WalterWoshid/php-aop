<?php

namespace Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns;

use Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns\Target\Other;
use Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns\Target\Selected;
use Okapi\Aop\Tests\Stubs\Etc\StackTrace;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class RegexWeavingTest extends TestCase
{
    public function testRegexSelectsClassesAndMethodsDuringWeaving(): void
    {
        Util::clearCache();
        Kernel::init();

        $selected = new Selected();
        static::assertSame([], StackTrace::getInstance()->getStackTrace());
        static::assertSame('saved', $selected->save());
        static::assertSame(['matched'], StackTrace::getInstance()->getStackTrace());
        static::assertSame('skipped', $selected->skip());
        static::assertSame('other', (new Other())->save());
        static::assertSame(['matched'], StackTrace::getInstance()->getStackTrace());
    }
}
