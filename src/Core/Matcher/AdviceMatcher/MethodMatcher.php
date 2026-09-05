<?php

namespace Okapi\Aop\Core\Matcher\AdviceMatcher;

use Okapi\Aop\Core\Container\AdviceType\MethodAdviceContainer;
use ReflectionMethod as BaseReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;

/**
 * # Method Matcher
 *
 * This class is used to match the given method advice container for the given
 * class.
 */
class MethodMatcher
{
    /**
     * Match the given method advice container for the given class.
     *
     * @param MethodAdviceContainer $methodAdviceContainer
     * @param BetterReflectionClass $refClassToMatch
     *
     * @return MethodAdviceContainer|null
     */
    public function match(
        MethodAdviceContainer $methodAdviceContainer,
        BetterReflectionClass $refClassToMatch,
    ): ?MethodAdviceContainer {
        $newMethodAdviceContainer = null;

        $refClassToMatchName = $refClassToMatch->getName();

        foreach ($refClassToMatch->getMethods() as $refMethodToMatch) {
            // Basically the same as $refClassToMatch->getImmediateMethods(),
            // but this also includes the methods from traits, because traits
            // cannot be woven
            $declaringClass = $refMethodToMatch->getDeclaringClass();
            $declaringClassName = $declaringClass->getName();
            if (!$declaringClass->isTrait() && $declaringClassName !== $refClassToMatchName) {
                continue;
            }

            $newMethodAdviceContainer = $methodAdviceContainer->isExplicit()
                ? $this->matchExplicit($methodAdviceContainer, $refMethodToMatch, $newMethodAdviceContainer)
                : $this->matchImplicit($methodAdviceContainer, $refMethodToMatch, $newMethodAdviceContainer);
        }

        return $newMethodAdviceContainer;
    }

    /**
     * Match explicit aspects.
     *
     * @param MethodAdviceContainer      $methodAdviceContainer
     * @param BetterReflectionMethod     $refMethodToMatch
     * @param MethodAdviceContainer|null $newMethodAdviceContainer
     *
     * @return MethodAdviceContainer|null
     */
    protected function matchExplicit(
        MethodAdviceContainer $methodAdviceContainer,
        BetterReflectionMethod $refMethodToMatch,
        ?MethodAdviceContainer $newMethodAdviceContainer,
    ): ?MethodAdviceContainer {
        $aspectClassName = $methodAdviceContainer->aspectClassName;

        // Match class attributes.
        $declaringClass = $refMethodToMatch->getDeclaringClass();
        foreach ($declaringClass->getAttributes() as $refAttribute) {
            if ($refAttribute->getName() !== $aspectClassName) {
                continue;
            }

            $methodRegex = $methodAdviceContainer->adviceAttributeInstance->method;
            // Advices without a method pattern apply to all methods.
            if ($methodRegex !== null && !$methodRegex->matches($refMethodToMatch->getName())) {
                continue;
            }
            $newMethodAdviceContainer = $this->createNewMethodAdviceContainer(
                $methodAdviceContainer,
                $newMethodAdviceContainer,
            );
            $newMethodAdviceContainer->addMatchedMethod($refMethodToMatch);
        }

        // Match method attributes.
        foreach ($refMethodToMatch->getAttributes() as $refAttribute) {
            if ($refAttribute->getName() !== $aspectClassName) {
                continue;
            }
            $newMethodAdviceContainer = $this->createNewMethodAdviceContainer(
                $methodAdviceContainer,
                $newMethodAdviceContainer,
            );
            $newMethodAdviceContainer->addMatchedMethod($refMethodToMatch);
        }

        return $newMethodAdviceContainer;
    }

    /**
     * Match implicit aspects.
     *
     * @param MethodAdviceContainer      $methodAdviceContainer
     * @param BetterReflectionMethod     $refMethodToMatch
     * @param MethodAdviceContainer|null $newMethodAdviceContainer
     *
     * @return MethodAdviceContainer|null
     */
    protected function matchImplicit(
        MethodAdviceContainer $methodAdviceContainer,
        BetterReflectionMethod $refMethodToMatch,
        ?MethodAdviceContainer $newMethodAdviceContainer,
    ): ?MethodAdviceContainer {
        $methodNameToMatch = $refMethodToMatch->getName();

        // Only public methods
        if (
            $methodAdviceContainer->adviceAttributeInstance->onlyPublicMethods
            && !($refMethodToMatch->getModifiers() & BaseReflectionMethod::IS_PUBLIC)
        ) {
            return $newMethodAdviceContainer;
        }

        // Intercept trait methods
        if (
            !$methodAdviceContainer->adviceAttributeInstance->interceptTraitMethods
            && $refMethodToMatch->getDeclaringClass()->isTrait()
        ) {
            return $newMethodAdviceContainer;
        }

        if ($methodAdviceContainer->adviceAttributeInstance->method?->matches($methodNameToMatch)) {
            $newMethodAdviceContainer = $this->createNewMethodAdviceContainer(
                $methodAdviceContainer,
                $newMethodAdviceContainer,
            );

            $newMethodAdviceContainer->addMatchedMethod($refMethodToMatch);
        }

        return $newMethodAdviceContainer;
    }

    /**
     * Create a new method advice container or return the given one.
     *
     * This method clones the existing method advice container to keep
     * track of the matched methods.
     *
     * @param MethodAdviceContainer      $methodAdviceContainer
     * @param MethodAdviceContainer|null $newMethodAdviceContainer
     *
     * @return MethodAdviceContainer
     */
    protected function createNewMethodAdviceContainer(
        MethodAdviceContainer $methodAdviceContainer,
        ?MethodAdviceContainer $newMethodAdviceContainer,
    ): MethodAdviceContainer {
        return $newMethodAdviceContainer ?? clone $methodAdviceContainer;
    }
}
