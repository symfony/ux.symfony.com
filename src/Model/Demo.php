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

final class Demo
{
    /**
     * @param string       $package  The UX package the demo showcases, as named in UxPackageRepository
     * @param list<string> $tags
     * @param string|null  $route    Defaults to "app_demo_{package}_{identifier}"
     * @param string|null  $template Defaults to "demos/{package}/{identifier}.html.twig"
     */
    public function __construct(
        private string $package,
        private string $identifier,
        private string $name,
        private string $description,
        private Person|string $author,
        private \DateTimeImmutable|string $publishedAt,
        private array $tags,
        private string $longDescription,
        private ?string $route = null,
        private ?string $template = null,
    ) {
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAuthor(): Person|string
    {
        return $this->author;
    }

    public function getPublishedAt(): \DateTimeImmutable|string
    {
        return $this->publishedAt;
    }

    public function getLongDescription(): string
    {
        return $this->longDescription;
    }

    public function getRoute(): string
    {
        return $this->route ?? \sprintf('app_demo_%s_%s', str_replace('-', '_', $this->package), str_replace('-', '_', $this->identifier));
    }

    public function getTemplate(): string
    {
        return $this->template ?? \sprintf('demos/%s/%s.html.twig', str_replace('-', '_', $this->package), str_replace('-', '_', $this->identifier));
    }

    public function getScreenshotFilename(?string $format = null): string
    {
        $directory = 'turbo' === $this->package ? 'turbo_demo' : 'live_demo';

        return 'images/'.$directory.'/'.$this->identifier.($format ? ('-'.$format) : '').'.png';
    }
}
