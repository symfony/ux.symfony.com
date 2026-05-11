<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Model\TurboDemo;

class TurboDemoRepository
{
    /**
     * @return array<TurboDemo>
     */
    public function findAll(): array
    {
        return [
            new TurboDemo(
                'infinite-scroll-2',
                name: 'Infinite Scroll - 2/2',
                description: 'Loading on-scroll, flexible layout grid and... more T-Shirts!',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'loading', 'scroll'],
                longDescription: <<<EOF
                    The second and final part of the **Infinite Scroll Series**, with a new range of (lovely) T-Shirts!
                    Now with `automatic loading on scroll`, a new trick and amazing `loading animations`!
                    EOF,
            ),
            new TurboDemo(
                'infinite-scroll',
                name: 'Infinite Scroll - 1/2',
                description: 'Load more items as you scroll down the page.',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'navigation'],
                longDescription: <<<EOF
                    Infinite scroll allows users to continuously load content as they scroll down the page.
                    `Part One` of this demo shows how to `append new items` to the page using `Turbo Frames` and `Turbo Stream`.
                    EOF,
            ),
        ];
    }

    public function getNext(string $identifier, bool $loop = false): ?TurboDemo
    {
        $demos = $this->findAll();
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $identifier) {
                return prev($demos) ?: ($loop ? end($demos) : null);
            }
            next($demos);
        }

        return null;
    }

    public function getPrevious(string $identifier, bool $loop = false): ?TurboDemo
    {
        $demos = $this->findAll();
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $identifier) {
                return next($demos) ?: ($loop ? reset($demos) : null);
            }
            next($demos);
        }

        return null;
    }

    public function getMostRecent(): TurboDemo
    {
        $demos = $this->findAll();

        return current($demos);
    }

    /**
     * @throws \InvalidArgumentException if the demo is not found
     */
    public function find(string $identifier): TurboDemo
    {
        $demos = $this->findAll();
        foreach ($demos as $demo) {
            if ($demo->getIdentifier() === $identifier) {
                return $demo;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown demo "%s"', $identifier));
    }
}
