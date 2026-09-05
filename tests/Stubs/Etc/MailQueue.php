<?php

namespace Okapi\Aop\Tests\Stubs\Etc;

use Okapi\Singleton\Singleton;

class MailQueue
{
    use Singleton;

    /** @var list<string> */
    private array $mails = [];

    public function addMail(string $mail): void
    {
        $this->mails[] = $mail;
    }

    /** @return list<string> */
    public function getMails(): array
    {
        return $this->mails;
    }
}
