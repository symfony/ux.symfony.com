<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Demo;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class InspectorCart
{
    use DefaultActionTrait;

    /** @var list<int> */
    #[LiveProp]
    public array $items = [];

    #[LiveAction]
    public function add(#[LiveArg] int $product): void
    {
        if ($product < 1 || $product > 6 || \in_array($product, $this->items, true)) {
            return;
        }

        $this->items[] = $product;
    }

    #[LiveAction]
    public function remove(#[LiveArg] int $product): void
    {
        $this->items = array_values(array_filter($this->items, static fn (int $item): bool => $item !== $product));
    }

    public function getSubtotal(): int
    {
        $prices = [1 => 24, 2 => 36, 3 => 18, 4 => 42, 5 => 29, 6 => 32];

        return array_sum(array_map(static fn (int $item): int => $prices[$item] ?? 0, $this->items));
    }

    #[LiveAction]
    public function clear(): void
    {
        $this->items = [];
    }
}
