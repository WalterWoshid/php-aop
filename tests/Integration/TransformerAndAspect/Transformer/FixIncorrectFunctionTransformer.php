<?php

namespace Okapi\Aop\Tests\Integration\TransformerAndAspect\Transformer;

use Microsoft\PhpParser\Node\QualifiedName;
use Okapi\Aop\Tests\Integration\TransformerAndAspect\Target\IncorrectFunctionAndReturnClass;
use Okapi\CodeTransformer\Transformer;
use Okapi\CodeTransformer\Transformer\Code;

class FixIncorrectFunctionTransformer extends Transformer
{
    public function getTargetClass(): string|array
    {
        return IncorrectFunctionAndReturnClass::class;
    }

    public function transform(Code $code): void
    {
        $sourceFileNode = $code->getSourceFileNode();

        /** @var \Microsoft\PhpParser\Node $node */
        foreach ($sourceFileNode->getDescendantNodes() as $node) {
            if (!($node instanceof QualifiedName && $node->getText() === 'is_int')) {
                continue;
            }

            $code->edit($node, 'is_float');
        }
    }
}
