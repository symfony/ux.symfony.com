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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Doc\RecipeDocRenderer;
use Symfony\UX\Toolkit\Doc\RenderedRecipeDoc;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Twig\Environment;

class ToolkitService
{
    /** @var array<string, RenderedRecipeDoc> */
    private array $renderedRecipes = [];

    public function __construct(
        private Environment $twig,
        private KitRepository $kitRepository,
        private UrlGeneratorInterface $urlGenerator,
        private ConverterFactory $converterFactory,
    ) {
    }

    public function getKit(string $kitId): Kit
    {
        return $this->kitRepository->get($kitId);
    }

    /**
     * @return array<string, Kit>
     */
    public function getKits(): array
    {
        return $this->kitRepository->all();
    }

    public function renderRecipeHtml(string $kitId, Recipe $recipe): string
    {
        $html = $this->renderRecipe($kitId, $recipe)->html;

        // Every recipe repeats the same installation steps, they would flood the docs search
        return preg_replace('#(<h2 id="content-installation">.*?</h2>\s*<\w+)#s', '$1 data-search-ignore', $html, 1) ?? $html;
    }

    /**
     * @return list<array{level: int, title: string, id: string, icon?: string}>
     */
    public function getRecipeTocItems(string $kitId, Recipe $recipe): array
    {
        return array_map(
            $this->decorateApiReferenceItem(...),
            $this->renderRecipe($kitId, $recipe)->tableOfContents,
        );
    }

    /**
     * The API-reference headings carry a type in their markup: a Stimulus controller reads
     * `data-controller="foo"` and a Twig component reads `<twig:Foo>`. Tag each with its icon for the
     * TOC, and shorten the title to just the bare name — the attribute/tag chrome is too wide for the
     * narrow sidebar; the on-page heading is untouched.
     *
     * @param array{level: int, title: string, id: string} $item
     *
     * @return array{level: int, title: string, id: string, icon?: string}
     */
    private function decorateApiReferenceItem(array $item): array
    {
        if (str_contains($item['title'], 'data-controller=')) {
            $item['title'] = preg_replace('/data-controller=(?:&quot;|")(.*?)(?:&quot;|")/', '$1', $item['title']);
            $item['icon'] = 'stimulus';

            return $item;
        }

        if (str_contains($item['title'], '&lt;twig:') || str_contains($item['title'], '<twig:')) {
            $item['title'] = preg_replace('/(?:&lt;|<)twig:(.*?)(?:&gt;|>)/', '$1', $item['title']);
            $item['icon'] = 'twig-components';
        }

        return $item;
    }

    /**
     * Renders the recipe README through the Toolkit's RecipeDocRenderer, reusing the site's CommonMark
     * environment so the docs get the site's Markdown treatment (highlighting, links, heading anchors,
     * and the shared Alert/Tabs/Popover extensions).
     */
    private function renderRecipe(string $kitId, Recipe $recipe): RenderedRecipeDoc
    {
        $cacheKey = $kitId.'/'.$recipe->name;

        // The Nth preview of the doc is served by the component page itself, with `?preview=N`.
        $previewUrlGenerator = new class($this->urlGenerator, $kitId, $recipe->name) implements PreviewUrlGenerator {
            private int $count = 0;

            public function __construct(
                private readonly UrlGeneratorInterface $urlGenerator,
                private readonly string $kitId,
                private readonly string $componentName,
            ) {
            }

            public function generate(string $code, CodeOptions $options): string
            {
                return $this->urlGenerator->generate('app_toolkit_component', [
                    'kitId' => $this->kitId,
                    'componentName' => $this->componentName,
                    'preview' => ++$this->count,
                ], UrlGeneratorInterface::ABSOLUTE_URL);
            }
        };

        return $this->renderedRecipes[$cacheKey] ??= (new RecipeDocRenderer($this->twig))->renderAsHtml(
            $this->getKit($kitId),
            $recipe,
            $previewUrlGenerator,
            ($this->converterFactory)()->getEnvironment(),
        );
    }
}
