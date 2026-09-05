<?php

namespace Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns;

use Okapi\Aop\Attributes\Aspect;
use Okapi\Aop\Attributes\Before;
use Okapi\Aop\Tests\Stubs\Etc\StackTrace;
use Okapi\Wildcards\Regex;

#[Aspect]
class PatternAspect
{
    #[Before(class: new Regex('~\\\\RegexPatterns\\\\Target\\\\Selected$~'), method: new Regex('~^SAVE$~i'))]
    public function record(): void
    {
        StackTrace::getInstance()->addTrace('matched');
    }
}
