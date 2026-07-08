<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

/**
 * For convenience and performance, official UX Toolkit kits are hardcoded.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
enum ToolkitKitId: string
{
    case Common = 'common';
    case Shadcn = 'shadcn';
    case Flowbite4 = 'flowbite-4';

    public function color(): string
    {
        return match ($this) {
            self::Common => '#7c3aed',
            self::Shadcn => 'hsl(0,0%,0%)',
            self::Flowbite4 => 'hsl(221,79%,48%)',
        };
    }
}
