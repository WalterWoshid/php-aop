<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\OnlyPublicMethods\Target;

trait TargetTrait
{
    public function traitHelloWorld(): void {}

    protected function traitHelloHere(): void {}
}
