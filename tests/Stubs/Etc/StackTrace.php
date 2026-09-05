<?php

namespace Okapi\Aop\Tests\Stubs\Etc;

use Okapi\Singleton\Singleton;

class StackTrace
{
    use Singleton;

    /** @var list<string> */
    private array $stackTrace = [];

    public function addTrace(string $trace): void
    {
        $this->stackTrace[] = $trace;
    }

    /** @return list<string> */
    public function getStackTrace(): array
    {
        return $this->stackTrace;
    }
}
