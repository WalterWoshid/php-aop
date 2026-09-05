<?php
namespace Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties;

use Okapi\Aop\PropertyAccess;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\ChildInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\ParentInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PromotedInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\SameTypeInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\TraitInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\MagicInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\StaticParent;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\StaticChild;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PublicInput;
use Okapi\Aop\Tests\Functional\AdviceBehavior\PrivateProperties\Target\PublicGrandchild;
use Okapi\Aop\Tests\Util;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class PrivatePropertiesTest extends TestCase
{
    protected function setUp(): void
    {
        Util::clearCache();
        Kernel::init();
    }

    public function testDifferentTypesAfterParentHasAlreadyLoaded(): void
    {
        self::assertSame(['parent'], (new ParentInput())->parentTokens());
        $child = new ChildInput();
        self::assertSame(['parent'], $child->parentTokens());
        self::assertSame('child', $child->childTokens());
        self::assertGreaterThanOrEqual(3, EverythingAspect::$calls);
    }

    public function testSameTypesRetainIndependentValues(): void
    {
        $child = new SameTypeInput();
        self::assertSame(['parent'], $child->parentTokens());
        self::assertSame(['child'], $child->childTokens());
    }

    public function testPromotedPropertyIsNotDuplicatedByForwardingConstructor(): void
    {
        $child = new PromotedInput('initial');
        PropertyAccess::set($child, 'tokens', 'changed', PromotedInput::class);
        self::assertSame('changed', $child->childTokens());
        self::assertSame(['parent'], $child->parentTokens());
    }

    public function testUnambiguousPropertyAccessDoesNotRequireScope(): void
    {
        $parent = new ParentInput();
        $values = PropertyAccess::get($parent, 'unique');
        $values[] = 'appended';
        PropertyAccess::set($parent, 'unique', $values);
        self::assertSame(['unique', 'appended'], $parent->unique());
        PropertyAccess::set($parent, 'unique', ['assigned']);
        self::assertSame(['assigned'], $parent->unique());
    }

    public function testExplicitScopeSelectsTheOriginalDeclaration(): void
    {
        $child = new ChildInput();
        PropertyAccess::set($child, 'tokens', ['updated parent'], ParentInput::class);
        PropertyAccess::set($child, 'tokens', 'updated child', ChildInput::class);
        self::assertSame(['updated parent'], $child->parentTokens());
        self::assertSame('updated child', $child->childTokens());
        self::assertSame(['updated parent'], PropertyAccess::get($child, 'tokens', ParentInput::class));
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
        self::assertSame(['parent'], $child->parentTokens());
        self::assertSame('trait', $child->childTokens());
        PropertyAccess::set($child, 'tokens', 'updated trait', TraitInput::class);
        self::assertSame('updated trait', $child->childTokens());
    }

    public function testCustomMagicAccessorsKeepVirtualPropertyBehavior(): void
    {
        $input = new MagicInput();
        self::assertSame('virtual', $input->example);
        $input->example = 'assigned';
        self::assertSame('assigned', $input->example);
        self::assertTrue(isset($input->example));
        self::assertSame(['example' => 'assigned'], PropertyAccess::get($input, 'values', MagicInput::class));
        unset($input->example);
        self::assertFalse(isset($input->example));
    }

    public function testStaticPrivatePropertiesHaveIndependentStorage(): void
    {
        self::assertSame(['parent'], StaticChild::parentTokens());
        self::assertSame('child', StaticChild::childTokens());
        PropertyAccess::set(StaticChild::class, 'tokens', ['updated'], StaticParent::class);
        PropertyAccess::set(StaticChild::class, 'tokens', 'updated child', StaticChild::class);
        self::assertSame(['updated'], StaticChild::parentTokens());
        self::assertSame('updated child', PropertyAccess::get(StaticChild::class, 'tokens', StaticChild::class));
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
        self::assertSame(['parent'], $child->parentTokens());
        self::assertSame('direct write', $child->childTokens());
        self::assertSame('direct write', PropertyAccess::get($child, 'tokens', PublicInput::class));
    }

    public function testNonPrivateOverridesShareStorage(): void
    {
        $child = new PublicGrandchild();
        PropertyAccess::set($child, 'tokens', 'shared', PublicInput::class);
        self::assertSame('shared', $child->tokens);
        self::assertSame('shared', PropertyAccess::get($child, 'tokens', PublicGrandchild::class));
        self::assertSame(['parent'], $child->parentTokens());
    }

    public function testUninitializedNullableReadThrowsWithoutInitializingProperty(): void
    {
        $child = new PublicInput();
        $property = new \ReflectionProperty(PublicInput::class . '__AopProxied', 'uninitialized');
        self::assertFalse($property->isInitialized($child));
        try {
            PropertyAccess::get($child, 'uninitialized', PublicInput::class);
            self::fail('Expected an uninitialized property error.');
        } catch (\Error $error) {
            self::assertStringContainsString('must not be accessed before initialization', $error->getMessage());
        }
        self::assertFalse($property->isInitialized($child));
    }

    public function testInvocationAccessorMutatesActualSubjectAndSupportsReferences(): void
    {
        $subject = new ParentInput();
        $subject->unique();
        $invocation = EverythingAspect::$invocation;
        self::assertSame($subject, $invocation->getSubject());
        $properties = $invocation->properties();
        $properties->unique[] = 'appended';
        $reference =& $properties->unique;
        $reference[] = 'reference';
        self::assertSame(['unique', 'appended', 'reference'], $subject->unique());
        self::assertTrue(isset($properties->unique));
        $properties->unique = ['assigned'];
        self::assertSame(['assigned'], $subject->unique());
        unset($properties->unique);
        self::assertFalse(isset($properties->unique));
        self::assertFalse(isset($properties->missing));
    }

    public function testInvocationAccessorSelectsParentAndChildScope(): void
    {
        $subject = new ChildInput();
        $subject->childTokens();
        $invocation = EverythingAspect::$invocation;
        $invocation->properties(ParentInput::class)->tokens = ['changed parent'];
        $invocation->properties(ChildInput::class)->tokens = 'changed child';
        self::assertSame(['changed parent'], $subject->parentTokens());
        self::assertSame('changed child', $subject->childTokens());
        $this->expectException(\LogicException::class);
        $invocation->properties()->tokens;
    }

    public function testStaticInvocationAccessor(): void
    {
        StaticChild::childTokens();
        $invocation = EverythingAspect::$invocation;
        self::assertNull($invocation->getSubject());
        $properties = $invocation->properties(StaticChild::class);
        $properties->tokens = 'assigned';
        self::assertSame('assigned', $properties->tokens);
        self::assertSame('assigned', StaticChild::childTokens());
        $this->expectException(\Error::class);
        unset($properties->tokens);
    }

    public function testAccessorDoesNotInitializeNullablePropertyOnRead(): void
    {
        $subject = new PublicInput();
        $subject->childTokens();
        $properties = EverythingAspect::$invocation->properties(PublicInput::class);
        self::assertFalse(isset($properties->uninitialized));
        $this->expectException(\Error::class);
        $properties->uninitialized;
    }
}
