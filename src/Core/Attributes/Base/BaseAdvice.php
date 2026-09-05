<?php

namespace Okapi\Aop\Core\Attributes\Base;

use InvalidArgumentException;
use Okapi\Aop\Core\Attributes\AdviceType\MethodAdvice;
use Okapi\Wildcards\Exceptions\WildcardException;
use Okapi\Wildcards\Regex;

/**
 * # Base advice
 *
 * This class is used as a base for all advice attributes.<br>
 * It should be extended from to categorize the advice types.
 *
 * @see MethodAdvice
 */
abstract class BaseAdvice extends BaseAttribute
{
    public ?Regex $class;

    /**
     * Base advice constructor.
     *
     * @param string|Regex|null $class Wildcard string or explicit regular expression for the class name.
     * @param int         $order The order of the advice.
     */
    public function __construct(
        string|Regex|null $class = null,
        public int $order = 0,
    ) {
        $this->class = self::resolvePattern($class, 'class');
    }

    /** @throws InvalidArgumentException If an explicit regular expression is invalid. */
    protected static function resolvePattern(string|Regex|null $pattern, string $parameter): ?Regex
    {
        if (!$pattern instanceof Regex) {
            return $pattern ? Regex::fromWildcard($pattern) : null;
        }

        try {
            // Compile the explicit expression now, before any class or method is matched.
            $pattern->matches('');
        } catch (WildcardException $exception) {
            throw new InvalidArgumentException(
                sprintf('Invalid %s regex "%s": %s', $parameter, $pattern->getRegex(), $exception->getMessage()),
                previous: $exception,
            );
        }

        return $pattern;
    }
}
