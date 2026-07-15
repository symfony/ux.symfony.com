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

use Tempest\Highlight\Highlighter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class HighlightExtension extends AbstractExtension
{
    public function __construct(
        private readonly Highlighter $highlighter,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight', $this->highlight(...), ['is_safe' => ['html']]),
        ];
    }

    public function highlight(string $code, string $language, ?int $startAt = null, bool $collapseClasses = false): string
    {
        $highlighted = null !== $startAt
            ? $this->highlighter->withGutter($startAt)->parse($code, $language)
            : $this->highlighter->parse($code, $language);

        return $collapseClasses ? $this->collapseClasses($highlighted) : $highlighted;
    }

    /**
     * Collapse `class="..."` attribute values in already-highlighted code into a
     * zero-JS, click-to-expand toggle. Tuned to the markup the Tempest highlighter
     * emits for an attribute: `<span class="hl-property">class</span>=&quot;…&quot;`.
     *
     * Uses a `<label>` wrapping a hidden checkbox so every element stays inline
     * (a `<details>`/`<summary>` renders with a line break inside `<pre>`).
     */
    private function collapseClasses(string $highlightedCode): string
    {
        return preg_replace_callback(
            '#(<span class="hl-property">class</span>=&quot;)(.+?)(&quot;)#',
            static fn (array $m): string => $m[1]
                .'<label class="hl-collapse" title="Toggle classes">'
                .'<input type="checkbox" class="hl-collapse-cb">'
                .'<span class="hl-collapse-dots">&hellip;</span>'
                .'<span class="hl-collapse-full">'.$m[2].'</span>'
                .'</label>'
                .$m[3],
            $highlightedCode,
        );
    }
}
