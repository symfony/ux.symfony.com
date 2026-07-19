<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Toolkit;

use App\Service\CommonMark\ConverterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\Toolkit\Doc\RecipeDocRenderer;
use Symfony\UX\Toolkit\Doc\RenderedRecipeDoc;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Twig\Environment;

class ToolkitService
{
    /** @var array<string, Kit> */
    private array $kits = [];

    /** @var array<string, RenderedRecipeDoc> */
    private array $renderedRecipes = [];

    public function __construct(
        private Environment $twig,
        #[Autowire(service: '.ux_toolkit.registry.local')]
        private LocalRegistry $localRegistry,
        private ComponentPreviewUrlGeneratorFactory $previewUrlGeneratorFactory,
        private ConverterFactory $converterFactory,
    ) {
    }

    public function getKit(string $kitId): Kit
    {
        return $this->kits[$kitId] ??= $this->localRegistry->getKit($kitId);
    }

    /**
     * @return array<string, Kit>
     */
    public function getKits(): array
    {
        return $this->kits = $this->localRegistry->getAvailableKits();
    }

    public function renderRecipeHtml(string $kitId, Recipe $recipe): string
    {
        return $this->renderRecipe($kitId, $recipe)->html;
    }

    /**
     * @return list<array{level: int, title: string, id: string}>
     */
    public function getRecipeTocItems(string $kitId, Recipe $recipe): array
    {
        return $this->renderRecipe($kitId, $recipe)->tableOfContents;
    }

    /**
     * Renders the recipe README through the Toolkit's RecipeDocRenderer, reusing the site's CommonMark
     * environment so the docs get the site's Markdown treatment (highlighting, links, heading anchors,
     * and the shared Alert/Tabs/Popover extensions).
     */
    private function renderRecipe(string $kitId, Recipe $recipe): RenderedRecipeDoc
    {
        $cacheKey = $kitId.'/'.$recipe->name;

        return $this->renderedRecipes[$cacheKey] ??= (new RecipeDocRenderer($this->twig))->renderAsHtml(
            $this->getKit($kitId),
            $recipe,
            $this->previewUrlGeneratorFactory->forKit($kitId),
            ($this->converterFactory)()->getEnvironment(),
        );
    }
}
