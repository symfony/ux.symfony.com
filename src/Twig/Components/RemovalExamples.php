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

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class RemovalExamples
{
    private const EXAMPLES = [
        'plain' => [
            'title' => 'Dismiss a component',
            'message' => 'Return LiveResponse::remove() from the action.',
            'buttonLabel' => 'Dismiss',
            'icon' => 'lucide:x',
            'action' => 'dismiss',
            'variant' => 'plain',
            'resultLabel' => 'Component removed',
            'resultDetail' => null,
        ],
        'events' => [
            'title' => 'Dispatch an event',
            'message' => 'Dispatch dismissed with id 42, then remove the component.',
            'buttonLabel' => 'Dispatch and dismiss',
            'icon' => 'lucide:radio',
            'action' => 'dismissWithEvents',
            'variant' => 'events',
            'resultLabel' => 'Browser event',
            'resultDetail' => 'dismissed { id: 42 }',
        ],
        'animated' => [
            'title' => 'Animate removal',
            'message' => 'Shake, then drop the component with CSS.',
            'buttonLabel' => 'Drop component',
            'icon' => 'lucide:arrow-down',
            'action' => 'dismiss',
            'variant' => 'animated',
            'resultLabel' => 'Animation complete',
            'resultDetail' => null,
        ],
    ];

    /**
     * @return array<string, array{title: string, message: string, buttonLabel: string, icon: string, action: string, variant: string, resultLabel: string, resultDetail: ?string}>
     */
    public function getExamples(): array
    {
        return self::EXAMPLES;
    }
}
