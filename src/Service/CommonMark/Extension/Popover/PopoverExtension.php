<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Popover;

use App\Service\CommonMark\Extension\Popover\Node\Popover;
use App\Service\CommonMark\Extension\Popover\Parser\PopoverParser;
use App\Service\CommonMark\Extension\Popover\Renderer\PopoverRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

final class PopoverExtension implements ExtensionInterface
{
    public function __construct(
        private \Twig\Environment $twig,
    ) {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addInlineParser(new PopoverParser())
            ->addRenderer(Popover::class, new PopoverRenderer($this->twig))
        ;
    }
}
