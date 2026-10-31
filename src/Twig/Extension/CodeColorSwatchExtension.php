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
use Twig\TwigFilter;

final class CodeColorSwatchExtension extends AbstractExtension
{
    public function getFilters(): iterable
    {
        yield new TwigFilter('color_swatches', [CodeColorSwatchRuntime::class, 'addSwatches'], ['is_safe' => ['html']]);
    }
}
