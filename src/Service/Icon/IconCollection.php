<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Icon;

/**
 * The icons of a single set, in Iconify's IconifyJSON format.
 *
 * @see https://iconify.design/docs/types/iconify-json.html
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconCollection
{
    private const DEFAULT_SIZE = 16;
    private const MAX_ALIAS_DEPTH = 10;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    public function svg(string $name): ?string
    {
        if (null === $icon = $this->resolve($name)) {
            return null;
        }

        $width = $icon['width'] ?? $this->data['width'] ?? self::DEFAULT_SIZE;
        $height = $icon['height'] ?? $this->data['height'] ?? self::DEFAULT_SIZE;
        $left = $icon['left'] ?? $this->data['left'] ?? 0;
        $top = $icon['top'] ?? $this->data['top'] ?? 0;

        return \sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="%s %s %s %s">%s</svg>',
            $width, $height, $left, $top, $width, $height, $icon['body'],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolve(string $name): ?array
    {
        $properties = [];

        // aliases point at another icon or alias; bail out rather than follow a cycle
        for ($depth = 0; $depth < self::MAX_ALIAS_DEPTH; ++$depth) {
            if (isset($this->data['icons'][$name])) {
                return [...$this->data['icons'][$name], ...$properties];
            }

            if (!isset($this->data['aliases'][$name]['parent'])) {
                return null;
            }

            $alias = $this->data['aliases'][$name];
            $name = $alias['parent'];
            unset($alias['parent']);

            $properties = [...$alias, ...$properties];
        }

        return null;
    }
}
