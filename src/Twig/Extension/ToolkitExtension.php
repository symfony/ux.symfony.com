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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ToolkitExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('toolkit_color', [ToolkitRuntime::class, 'kitColor']);
    }
}
