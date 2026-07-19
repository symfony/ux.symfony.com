<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Extension;

use App\Service\Toolkit\ToolkitService;
use Twig\Extension\RuntimeExtensionInterface;

final class ToolkitRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ToolkitService $toolkitService,
    ) {
    }

    public function kitColor(string $kitId): string
    {
        return $this->toolkitService->getKit($kitId)->manifest->color ?? '#000';
    }
}
