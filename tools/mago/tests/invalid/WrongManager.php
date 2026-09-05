<?php

namespace Okapi\Aop\MagoTests;

use Okapi\CodeTransformer\Core\Container\TransformerManager;

function wrongManager(): void
{
    $manager = new TransformerManager();
    $manager->registerCustomDependencyInjectionHandler(
        /** @param class-string $name */ static fn(string $name): string => 'invalid',
    );
}
