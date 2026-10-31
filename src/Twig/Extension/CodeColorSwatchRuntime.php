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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\Css\Engine\Engine;
use Twig\Extension\RuntimeExtensionInterface;

final class CodeColorSwatchRuntime implements RuntimeExtensionInterface
{
    private const COLOR_PATTERN = '/[\'"](?<hex>#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8}))[\'"]'
        .'|[\'"](?<token>[\w.-]+)[\'"]'
        .'|\{colors\.(?<reference>[\w.-]+)\}'
        .'|(?<variable>var\(--colors-[\w-]+\))'
        .'|(?<function>\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\([\w\s.,%\/+-]*\))/i';

    /**
     * @var array<string, string>|null
     */
    private ?array $colors = null;

    public function __construct(
        #[Autowire(service: 'ux_css.engine')]
        private readonly Engine $engine,
    ) {
    }

    public function addSwatches(string $highlightedCode): string
    {
        return implode("\n", array_map($this->addSwatch(...), explode("\n", $highlightedCode)));
    }

    private function addSwatch(string $line): string
    {
        $color = $this->findColor(html_entity_decode(strip_tags($line), \ENT_QUOTES | \ENT_HTML5));
        $style = null !== $color ? ' style="--swatch: '.htmlspecialchars($color).'"' : '';

        return '<span class="hl-swatch"'.$style.' aria-hidden="true"></span>'.$line;
    }

    private function findColor(string $text): ?string
    {
        preg_match_all(self::COLOR_PATTERN, $text, $matches, \PREG_SET_ORDER | \PREG_UNMATCHED_AS_NULL);

        foreach ($matches as $match) {
            $color = match (true) {
                null !== $match['hex'] => $match['hex'],
                null !== $match['token'] => $this->colors()[$match['token']] ?? null,
                null !== $match['reference'] => $this->colors()[$match['reference']] ?? null,
                null !== $match['function'] => $match['function'],
                default => \in_array($match['variable'], $this->colors(), true) ? $match['variable'] : null,
            };

            if (null !== $color) {
                return $color;
            }
        }

        return null;
    }

    /**
     * @return array<string, string> the CSS variable of each color token, indexed by token name
     */
    private function colors(): array
    {
        return $this->colors ??= $this->engine->tokens()->getCategoryValues('colors') ?? [];
    }
}
