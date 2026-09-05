<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\AdviceOrder\Target;

class ArticleManager
{
    public function createArticle(string $title, string $content): void
    {
        // Code to create and save an article object
    }
}
