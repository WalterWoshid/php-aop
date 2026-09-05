<?php

namespace Okapi\Aop\MagoTests;

use Okapi\CodeTransformer\Core\Container\TransformerManager;
use Okapi\CodeTransformer\Transformer;

function valid_managers(): void
{
    $base = new TransformerManager();
    $base->registerCustomDependencyInjectionHandler(
        /** @param class-string<Transformer> $name */ static fn(string $name): Transformer => new ExampleTransformer(),
    );
}
