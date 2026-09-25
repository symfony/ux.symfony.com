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
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class InspectorTotal
{
    use DefaultActionTrait;

    #[LiveProp(updateFromParent: true)]
    public int $subtotal = 0;

    #[LiveProp]
    public bool $express = false;

    #[LiveListener('inspector:delivery-changed')]
    public function updateDelivery(#[LiveArg] bool $express): void
    {
        $this->express = $express;
    }

    public function getTotal(): int
    {
        return $this->subtotal + ($this->express ? 12 : 0);
    }
}
