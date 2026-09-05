<?php

namespace Okapi\Aop\MagoTests;

use Okapi\CodeTransformer\Transformer;
use Okapi\CodeTransformer\Transformer\Code;

class ExampleTransformer extends Transformer
{
    public function getTargetClass(): string|array
    {
        return \stdClass::class;
    }

    public function transform(Code $code): void {}
}
