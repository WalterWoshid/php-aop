<?php

namespace Okapi\Aop\Tests\Integration\RegexPatterns;

use Okapi\Aop\Attributes\After;
use Okapi\Wildcards\Regex;

class AttributeFixture
{
    #[After(class: new Regex('~^App\\\\Controller$~'), method: new Regex('~^[a-z]+$~'))]
    public function advice(): void {}
}
