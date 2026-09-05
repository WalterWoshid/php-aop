<?php

namespace Okapi\Aop\Tests\Performance\Service;

use Okapi\Aop\Tests\Performance\Target\Numbers;

interface NumbersServiceInterface
{
    public function addToNumbers(int $number, Numbers $numbers): void;
}
