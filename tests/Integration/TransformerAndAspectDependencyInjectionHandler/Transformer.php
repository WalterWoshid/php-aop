<?php

namespace Okapi\Aop\Tests\Integration\TransformerAndAspectDependencyInjectionHandler;

use Microsoft\PhpParser\Node\DelimitedList\QualifiedNameList;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\NumericLiteral;
use Okapi\CodeTransformer\Transformer as TransformerClass;
use Okapi\CodeTransformer\Transformer\Code;

class Transformer extends TransformerClass
{
    public function getTargetClass(): string|array
    {
        return Target::class;
    }

    /** @noinspection PhpPossiblePolymorphicInvocationInspection */
    public function transform(Code $code): void
    {
        $sourceFileNode = $code->getSourceFileNode();

        /** @var \Microsoft\PhpParser\Node $node */
        foreach ($sourceFileNode->getDescendantNodes() as $node) {
            $method = $node->getFirstAncestor(MethodDeclaration::class);
            if (
                $node instanceof QualifiedNameList
                && $method instanceof MethodDeclaration
                && $method->getName() === 'answer'
            ) {
                $code->edit($node, 'int|float');
            }

            if (
                $node instanceof NumericLiteral
                && $method instanceof MethodDeclaration
                && $method->getName() === 'answer'
            ) {
                $text = $node->getText();
                $code->edit($node, "{$text}.69");
            }
        }
    }
}
