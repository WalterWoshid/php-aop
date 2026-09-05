<?php

namespace Okapi\Aop\Tests\Functional\AdviceApplication\MultipleExplicitMethodLevelAspects\Target;

use Exception;
use Okapi\Aop\Tests\Functional\AdviceApplication\MultipleExplicitMethodLevelAspects\Aspect\SecurityAspect;

class AccountService
{
    /** @var array<int, string> */
    private array $accounts = [];

    /** @param array{id: string} $userData */
    #[SecurityAspect]
    public function createAccount(array $userData): void
    {
        $this->accounts[] = $userData['id'];
    }

    #[SecurityAspect]
    public function deleteAccount(string $accountId): void
    {
        $accountIndex = array_search($accountId, $this->accounts, true);

        if ($accountIndex === false) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception("Account with id {$accountId} not found.");
        }

        unset($this->accounts[$accountIndex]);
    }

    /** @return array<int, string> */
    public function getAccounts(): array
    {
        return $this->accounts;
    }
}
