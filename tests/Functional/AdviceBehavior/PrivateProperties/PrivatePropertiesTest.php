<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties;

use Okapi\Aop\PropertyAccess;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\ChildInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\MagicInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\ParentInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PromotedInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PublicGrandchild;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PublicInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\SameTypeInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\StaticChild;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\StaticParent;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\TraitInput;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class PrivatePropertiesTest extends TestCase
{
    /** Expose the fixture's magic properties as a structural object view. */
    private function propertyView(object $accessor): object
    {
        return $accessor;
    }

    protected function setUp(): void
    {
        Util::clearCache();
        Kernel::init();
    }

    public function testDifferentTypesAfterParentHasAlreadyLoaded(): void
    {
        static::assertSame(['parent'], (new ParentInput())->parentTokens());
        $child = new ChildInput();
        static::assertSame(['parent'], $child->parentTokens());
        static::assertSame('child', $child->childTokens());
        static::assertGreaterThanOrEqual(3, EverythingAspect::$calls);
    }

    public function testSameTypesRetainIndependentValues(): void
    {
        $child = new SameTypeInput();
        static::assertSame(['parent'], $child->parentTokens());
        static::assertSame(['child'], $child->childTokens());
    }

    public function testPromotedPropertyIsNotDuplicatedByForwardingConstructor(): void
    {
        $child = new PromotedInput('initial');
        PropertyAccess::set($child, 'tokens', 'changed', PromotedInput::class);
        static::assertSame('changed', $child->childTokens());
        static::assertSame(['parent'], $child->parentTokens());
    }

    public function testUnambiguousPropertyAccessDoesNotRequireScope(): void
    {
        $parent = new ParentInput();
        /** @var list<string> $values */
        $values = PropertyAccess::get($parent, 'unique');
        $values[] = 'appended';
        PropertyAccess::set($parent, 'unique', $values);
        static::assertSame(['unique', 'appended'], $parent->unique());
        PropertyAccess::set($parent, 'unique', ['assigned']);
        static::assertSame(['assigned'], $parent->unique());
    }

    public function testExplicitScopeSelectsTheOriginalDeclaration(): void
    {
        $child = new ChildInput();
        PropertyAccess::set($child, 'tokens', ['updated parent'], ParentInput::class);
        PropertyAccess::set($child, 'tokens', 'updated child', ChildInput::class);
        static::assertSame(['updated parent'], $child->parentTokens());
        static::assertSame('updated child', $child->childTokens());
        static::assertSame(['updated parent'], PropertyAccess::get($child, 'tokens', ParentInput::class));
    }

    public function testAmbiguousAccessRequiresExplicitScope(): void
    {
        $child = new SameTypeInput();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ambiguous');
        PropertyAccess::get($child, 'tokens');
    }

    public function testTraitPropertyRemainsSeparate(): void
    {
        $child = new TraitInput();
        static::assertSame(['parent'], $child->parentTokens());
        static::assertSame('trait', $child->childTokens());
        PropertyAccess::set($child, 'tokens', 'updated trait', TraitInput::class);
        static::assertSame('updated trait', $child->childTokens());
    }

    public function testCustomMagicAccessorsKeepVirtualPropertyBehavior(): void
    {
        $input = new MagicInput();
        static::assertSame('virtual', $input->example);
        $input->example = 'assigned';
        static::assertSame('assigned', $input->example);
        static::assertTrue(isset($input->example));
        static::assertSame(['example' => 'assigned'], PropertyAccess::get($input, 'values', MagicInput::class));
        unset($input->example);
        static::assertFalse(isset($input->example));
    }

    public function testStaticPrivatePropertiesHaveIndependentStorage(): void
    {
        static::assertSame(['parent'], StaticChild::parentTokens());
        static::assertSame('child', StaticChild::childTokens());
        PropertyAccess::set(StaticChild::class, 'tokens', ['updated'], StaticParent::class);
        PropertyAccess::set(StaticChild::class, 'tokens', 'updated child', StaticChild::class);
        static::assertSame(['updated'], StaticChild::parentTokens());
        static::assertSame('updated child', PropertyAccess::get(StaticChild::class, 'tokens', StaticChild::class));
    }

    public function testUnknownScopeDoesNotFallBackToAnotherDeclaration(): void
    {
        $this->expectException(\ReflectionException::class);
        PropertyAccess::get(new ParentInput(), 'tokens', \stdClass::class);
    }

    public function testInstanceAccessRequiresAnObject(): void
    {
        $this->expectException(\LogicException::class);
        PropertyAccess::get(ParentInput::class, 'tokens');
    }

    public function testScopedWritesEnforceDeclaredType(): void
    {
        $this->expectException(\TypeError::class);
        PropertyAccess::set(new ParentInput(), 'tokens', 'not an array', ParentInput::class);
    }

    public function testPrivateParentAndPublicChildStayIndependent(): void
    {
        $child = new PublicInput();
        $child->tokens = 'direct write';
        static::assertSame(['parent'], $child->parentTokens());
        static::assertSame('direct write', $child->childTokens());
        static::assertSame('direct write', PropertyAccess::get($child, 'tokens', PublicInput::class));
    }

    public function testNonPrivateOverridesShareStorage(): void
    {
        $child = new PublicGrandchild();
        PropertyAccess::set($child, 'tokens', 'shared', PublicInput::class);
        static::assertSame('shared', $child->tokens);
        static::assertSame('shared', PropertyAccess::get($child, 'tokens', PublicGrandchild::class));
        static::assertSame(['parent'], $child->parentTokens());
    }

    public function testUninitializedNullableReadThrowsWithoutInitializingProperty(): void
    {
        $child = new PublicInput();
        $property = new \ReflectionProperty(PublicInput::class . '__AopProxied', 'uninitialized');
        static::assertFalse($property->isInitialized($child));
        static::assertFalse($child->hasUninitializedValue());
        try {
            PropertyAccess::get($child, 'uninitialized', PublicInput::class);
            static::fail('Expected an uninitialized property error.');
        } catch (\Throwable $error) {
            static::assertInstanceOf(\Error::class, $error);
            static::assertStringContainsString('must not be accessed before initialization', $error->getMessage());
        }
        static::assertFalse($property->isInitialized($child));
        static::assertFalse($child->hasUninitializedValue());
    }

    public function testInvocationAccessorMutatesActualSubjectAndSupportsReferences(): void
    {
        $subject = new ParentInput();
        $subject->unique();
        $invocation = EverythingAspect::$invocation;
        static::assertSame($subject, $invocation->getSubject());
        /** @var object{unique: list<string>, missing?: mixed} $properties */
        $properties = $this->propertyView($invocation->properties());
        $properties->unique[] = 'appended';
        $reference = &$properties->unique;
        $reference[] = 'reference';
        static::assertSame(['unique', 'appended', 'reference'], $subject->unique());
        static::assertTrue(isset($properties->unique));
        $properties->unique = ['assigned'];
        static::assertSame(['assigned'], $subject->unique());
        unset($properties->unique);
        static::assertFalse(isset($properties->unique));
        static::assertFalse(isset($properties->missing));
    }

    public function testInvocationAccessorSelectsParentAndChildScope(): void
    {
        $subject = new ChildInput();
        $subject->childTokens();
        $invocation = EverythingAspect::$invocation;
        $invocation->properties(ParentInput::class)->__set('tokens', ['changed parent']);
        $invocation->properties(ChildInput::class)->__set('tokens', 'changed child');
        static::assertSame(['changed parent'], $subject->parentTokens());
        static::assertSame('changed child', $subject->childTokens());
        $this->expectException(\LogicException::class);
        $invocation->properties()->__get('tokens');
    }

    public function testStaticInvocationAccessor(): void
    {
        StaticChild::childTokens();
        $invocation = EverythingAspect::$invocation;
        static::assertNull($invocation->getSubject());
        /** @var object{tokens: string} $properties */
        $properties = $this->propertyView($invocation->properties(StaticChild::class));
        $properties->tokens = 'assigned';
        static::assertSame('assigned', $properties->tokens);
        static::assertSame('assigned', StaticChild::childTokens());
        $this->expectException(\Error::class);
        unset($properties->tokens);
    }

    public function testAccessorDoesNotInitializeNullablePropertyOnRead(): void
    {
        $subject = new PublicInput();
        $subject->childTokens();
        /** @var object{uninitialized: ?string} $properties */
        $properties = $this->propertyView(EverythingAspect::$invocation->properties(PublicInput::class));
        static::assertFalse(isset($properties->uninitialized));
        $this->expectException(\Error::class);
        static::fail('Expected an uninitialized property error: ' . var_export($properties->uninitialized, true));
    }

    public function testUnsetPropertyReadDoesNotInvokeSubjectMagicGetter(): void
    {
        $subject = new class {
            private string $value = 'initial';
            public int $calls = 0;

            public function __get(string $name): mixed
            {
                $this->calls++;
                return 'magic';
            }
        };
        /** @var object{value: string} $properties */
        $properties = $this->propertyView(new \Okapi\Aop\Invocation\PropertyAccessor($subject));
        unset($properties->value);
        try {
            static::fail('Expected uninitialized property error: ' . $properties->value);
        } catch (\Throwable $error) {
            static::assertInstanceOf(\Error::class, $error);
            static::assertSame(0, $subject->calls);
        }
    }

    public function testRedeclaredPublicStaticPropertiesRequireScope(): void
    {
        $parent = Target\SharedStaticParent::class;
        $child = Target\SharedStaticChild::class;
        /** @var object{value: int} $properties */
        $properties = $this->propertyView(new \Okapi\Aop\Invocation\PropertyAccessor($child, $parent));
        $properties->value = 3;
        static::assertSame(3, $parent::$value);
        static::assertSame(2, $child::$value);
        $this->expectException(\LogicException::class);
        PropertyAccess::get($child, 'value');
    }

    public function testWriteAfterUnsetPreservesNativeMagicSetterBehavior(): void
    {
        $subject = new class {
            private string $value = 'initial';
            public int $calls = 0;

            public function __set(string $name, mixed $value): void
            {
                $this->calls++;
            }
        };
        /** @var object{value: string} $properties */
        $properties = $this->propertyView(new \Okapi\Aop\Invocation\PropertyAccessor($subject));
        unset($properties->value);
        $properties->value = 'new';
        static::assertSame(1, $subject->calls);
        static::assertFalse(isset($properties->value));
    }
}
