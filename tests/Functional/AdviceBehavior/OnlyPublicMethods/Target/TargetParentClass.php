<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\OnlyPublicMethods\Target;

class TargetParentClass
{
    public function parentHelloWorld(): void {}

    protected function parentHelloHere(): void {}
}
