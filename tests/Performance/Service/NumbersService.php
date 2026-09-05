<?php

namespace Okapi\Aop\Tests\Performance\Service;

use Okapi\Aop\Tests\Performance\Target\Numbers;

class NumbersService implements \Okapi\Aop\Tests\Performance\Service\NumbersServiceInterface
{
    public function addToNumbers(int $number, Numbers $numbers): void
    {
        $numbers->add($number);
    }
}
