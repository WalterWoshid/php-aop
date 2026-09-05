<?php

namespace Okapi\Aop\Tests\Functional\AspectMatching\RegexPatterns\Target;

class Selected
{
    public function __construct() {}

    public function save(): string
    {
        return 'saved';
    }

    public function skip(): string
    {
        return 'skipped';
    }
}
