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

use App\Enum\ToolkitKitId;
use App\Service\CommonMark\ConverterFactory;
use App\Service\Toolkit\ToolkitService;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\TableOfContents\Node\TableOfContents as TocNode;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Parser\MarkdownParser;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Recipe $component;

    private ?string $markdownContent = null;

    public function __construct(
        private readonly ToolkitService $toolkitService,
        private readonly ConverterFactory $converterFactory,
    ) {
    }

    public function getMarkdownContent(): string
    {
        return $this->markdownContent ??= $this->toolkitService->renderRecipeMarkdown($this->kitId, $this->component);
    }

    /**
     * @return list<array{level: int, title: string, id: string}>
     */
    public function getTocItems(): array
    {
        $converter = ($this->converterFactory)(withTableOfContents: true);
        $document = new MarkdownParser($converter->getEnvironment())
            ->parse($this->getMarkdownContent());

        $headingLevels = [];
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof Heading) {
                $id = $node->data->get('attributes/id', null);
                if (null !== $id) {
                    $headingLevels[$id] = $node->getLevel();
                }
            }
        }

        $tocNode = $document->firstChild();
        if (!$tocNode instanceof TocNode) {
            return [];
        }

        $items = [];
        foreach ($tocNode->iterator() as $node) {
            if (!$node instanceof Link) {
                continue;
            }

            $id = ltrim($node->getUrl(), '#');
            $firstChild = $node->firstChild();
            $title = $firstChild instanceof Text ? $firstChild->getLiteral() : '';

            $items[] = [
                'level' => $headingLevels[$id] ?? 2,
                'title' => $title,
                'id' => $id,
            ];
        }

        return $items;
    }
}
