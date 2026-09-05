<?php

namespace Okapi\Aop\Core\Container;

use Okapi\Aop\Component\ComponentType;
use Okapi\CodeTransformer\Core\Container\TransformerManager as CodeTransformerTransformerManager;

class TransformerManager extends CodeTransformerTransformerManager
{
    /** @return list<ComponentType> */
    protected function getAdditionalDependencyInjectionParams(): array
    {
        return [ComponentType::TRANSFORMER];
    }
}
