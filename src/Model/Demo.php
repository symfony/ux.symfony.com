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

abstract class Demo
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        private string $identifier,
        private string $name,
        private string $description,
        private Person|string $author,
        private \DateTimeImmutable|string $publishedAt,
        private array $tags,
    ) {
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

    /**
     * The UX package the demo showcases, as named in UxPackageRepository.
     */
    abstract public function getPackage(): string;

    abstract public function getRoute(): string;

    abstract public function getScreenshotFilename(?string $format = null): string;

    abstract public function getTemplate(): string;

    abstract public function getLongDescription(): string;
}
