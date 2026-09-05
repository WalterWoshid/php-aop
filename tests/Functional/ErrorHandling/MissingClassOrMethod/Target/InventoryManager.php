<?php

/** @noinspection PhpUnused */
namespace Okapi\Aop\Tests\Functional\ErrorHandling\MissingClassOrMethod\Target;

class InventoryManager
{
    /** @var array<string, int> */
    private array $items = [];

    public function addItem(string $itemName, int $quantity): void
    {
        $this->items[$itemName] = $quantity;
    }

    public function removeItem(string $itemName): void
    {
        unset($this->items[$itemName]);
    }

    public function getQuantity(string $itemName): int
    {
        return $this->items[$itemName] ?? 0;
    }
}
