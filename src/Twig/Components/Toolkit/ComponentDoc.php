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
use App\Service\Toolkit\ToolkitService;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Recipe $component;

    /** @var list<array{level: int, title: string, id: string}> */
    public array $tocItems = [];

    private ?string $markdownContent = null;

    public function __construct(
        private readonly ToolkitService $toolkitService,
    ) {
    }

    public function getMarkdownContent(): string
    {
        return $this->markdownContent ??= $this->toolkitService->renderRecipeMarkdown($this->kitId, $this->component);
    }
}
