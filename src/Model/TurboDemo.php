<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

final class TurboDemo extends Demo
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        string $identifier,
        string $name,
        string $description,
        string $author,
        string $publishedAt,
        array $tags,
        private string $longDescription,
    ) {
        parent::__construct($identifier, $name, $description, $author, $publishedAt, $tags);
    }

    public function getRoute(): string
    {
        return 'app_demo_turbo_'.str_replace('-', '_', parent::getIdentifier());
    }

    public function getScreenshotFilename(?string $format = null): string
    {
        return 'images/turbo_demo/'.parent::getIdentifier().($format ? ('-'.$format) : '').'.png';
    }

    public function getTemplate(): string
    {
        return \sprintf('demos/turbo/%s.html.twig', str_replace('-', '_', parent::getIdentifier()));
    }

    public function getLongDescription(): string
    {
        return $this->longDescription;
    }
}
