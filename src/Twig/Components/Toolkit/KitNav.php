<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Toolkit;

use App\Service\Toolkit\BlockSection;
use App\Service\Toolkit\BlockSectionResolver;
use App\Service\Toolkit\ToolkitService;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class KitNav
{
    public string $kitId;

    private ?Kit $kit = null;

    public function __construct(
        private readonly ToolkitService $toolkitService,
        private readonly BlockSectionResolver $blockSectionResolver,
    ) {
    }

    /**
     * @return list<Recipe>
     */
    public function getComponents(): array
    {
        return array_values($this->getKit()->getRecipes(RecipeType::Component));
    }

    /**
     * @return list<BlockSection>
     */
    public function getBlockSections(): array
    {
        return $this->blockSectionResolver->forKit($this->getKit());
    }

    private function getKit(): Kit
    {
        return $this->kit ??= $this->toolkitService->getKit($this->kitId);
    }
}
