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
final class Alert
{
    public ?string $variant = null;

    public function getIcon(): string
    {
        return match ($this->variant) {
            'danger' => 'bi:exclamation-circle',
            'warning' => 'bi:exclamation-triangle',
            'info' => 'bi:info-circle',
            default => 'bi:check-circle',
        };
    }
}
