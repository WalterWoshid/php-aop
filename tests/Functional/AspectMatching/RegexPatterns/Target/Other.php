<?php

namespace Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns\Target;

class Other
{
    public function save(): string
    {
        return 'other';
    }
}
