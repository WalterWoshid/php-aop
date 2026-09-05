<?php

namespace Okapi\Aop\Tests\Integration\RegexPatterns;

use InvalidArgumentException;
use Okapi\Aop\Attributes\After;
use Okapi\Aop\Attributes\Around;
use Okapi\Aop\Attributes\Before;
use Okapi\Wildcards\Regex;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RegexPatternsTest extends TestCase
{
    public function testAllAdviceTypesAcceptRegexObjects(): void
    {
        $class = new Regex('~^App\\\\Controller$~');
        $method = new Regex('~^[a-z][a-z0-9_]*$~i');
        foreach ([new Before($class, $method), new Around($class, $method), new After($class, $method)] as $advice) {
            static::assertSame($class, $advice->class);
            static::assertSame($method, $advice->method);
            static::assertTrue($class->matches('App\\Controller'));
            static::assertTrue($method->matches('SAVE'));
            static::assertFalse($method->matches('__construct'));
        }
    }

    public function testRegexAndWildcardPatternsCanBeMixed(): void
    {
        $wildcardClass = new After(class: 'App\\*', method: new Regex('~^save$~'));
        static::assertNotNull($wildcardClass->class);
        static::assertTrue($wildcardClass->class->matches('App\\Controller'));
        $wildcardMethod = new After(class: new Regex('~Controller$~'), method: 'save*');
        static::assertNotNull($wildcardMethod->method);
        static::assertTrue($wildcardMethod->method->matches('saveAll'));
        static::assertNotNull($wildcardMethod->class);
        static::assertTrue($wildcardMethod->class->matches('App\\Controller'));
    }

    public function testRegexLookingStringsRemainWildcards(): void
    {
        $advice = new After(class: 'App\\*', method: '/^save$/');
        static::assertNotNull($advice->method);
        static::assertTrue($advice->method->matches('/^save$/'));
        static::assertFalse($advice->method->matches('save'));
    }

    public function testAbsentPatternsRemainAbsent(): void
    {
        foreach ([new After(), new After('', ''), new After('0', '0')] as $advice) {
            static::assertNull($advice->class);
            static::assertNull($advice->method);
        }
    }

    public function testInvalidClassRegexFailsDuringConstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid class regex');
        new After(class: new Regex('/[/'), method: '*');
    }

    public function testInvalidMethodRegexFailsDuringConstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid method regex');
        new After(class: '*', method: new Regex('missing delimiters'));
    }

    public function testRegexObjectsCanBeUsedInPhpAttributes(): void
    {
        $reflection = new ReflectionMethod(AttributeFixture::class, 'advice');
        $attribute = $reflection->getAttributes(After::class)[0]->newInstance();
        static::assertInstanceOf(After::class, $attribute);
        static::assertNotNull($attribute->class);
        static::assertNotNull($attribute->method);
        static::assertTrue($attribute->class->matches('App\\Controller'));
        static::assertTrue($attribute->method->matches('save'));
        static::assertFalse($attribute->method->matches('__construct'));
    }
}
