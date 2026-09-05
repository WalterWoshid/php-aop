<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\TraitAdvice\Target;

trait RouteCaching
{
    /** @return array<string, list<string>> */
    public function getRoutes(): array
    {
        return [
            'GET' => ['/users', 'UserController@index'],
        ];
    }
}
