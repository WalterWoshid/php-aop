<?php

namespace Okapi\CodeTransformer;

use Closure;

/**
 * The callback is an extension point: subclasses can install a manager that
 * supplies additional arguments. Preserve the default transformer's contract.
 *
 * @template THandler of Closure = Closure(class-string<Transformer>): Transformer
 */
abstract class CodeTransformerKernel
{
    /** @return THandler|null */
    protected function dependencyInjectionHandler(): ?Closure
    {
        return null;
    }
}
