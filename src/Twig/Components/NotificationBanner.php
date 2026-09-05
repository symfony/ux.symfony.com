<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent]
final class NotificationBanner
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public string $title = '';

    #[LiveProp]
    public string $message = '';

    #[LiveProp]
    public string $buttonLabel = 'Dismiss component';

    #[LiveProp]
    public string $icon = 'lucide:x';

    #[LiveProp]
    public string $action = 'dismiss';

    #[LiveProp]
    public string $variant = 'plain';

    #[LiveAction]
    public function dismiss(): LiveResponse
    {
        return LiveResponse::remove();
    }

    #[LiveAction]
    public function dismissWithEvents(): LiveResponse
    {
        $this->dispatchBrowserEvent(
            'dismissed',
            ['id' => 42],
        );

        return LiveResponse::remove();
    }
}
