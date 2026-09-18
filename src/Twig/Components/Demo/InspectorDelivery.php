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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class InspectorDelivery
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public bool $express = false;

    #[LiveAction]
    public function toggle(): void
    {
        $this->express = !$this->express;
        $this->emit('inspector:delivery-changed', ['express' => $this->express]);
    }
}
