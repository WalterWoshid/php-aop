<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\OnlyPublicMethods\Target;

class TargetClass extends TargetParentClass
{
    use TargetTrait;

    public function helloWorld(): void {}

    protected function helloHere(): void {}

    public function askParentHelloHere(): void
    {
        parent::parentHelloHere();
    }

    public function askTraitHelloHere(): void
    {
        $this->traitHelloHere();
    }
}
