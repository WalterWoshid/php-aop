<?php

namespace Okapi\Aop\Tests\Stubs\Etc;

use Okapi\Singleton\Singleton;

class Logger
{
    use Singleton;

    /** @var list<string> */
    private array $log = [];

    public function log(string $message): void
    {
        $this->log[] = $message;
    }

    /** @return list<string> */
    public function getLogs(): array
    {
        return $this->log;
    }
}
