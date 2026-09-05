<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\Include\Target;

class SecureDatabaseService
{
    /** @var array<string, int>|null */
    private ?array $data = null;

    public function load(): self
    {
        if ($this->data === null) {
            /** @var array<string, int> $data */
            $data = require dirname(__DIR__, 3) . '/AdviceBehavior/Include/Database/data.php';
            $this->data = $data;
        }

        return $this;
    }

    /** @return array<string, int> */
    public function getData(): array
    {
        if ($this->data === null) {
            $this->load();
        }

        return $this->data;
    }
}
