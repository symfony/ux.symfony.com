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
                name: 'Turbo Infinite Scroll - 2/2',
                description: 'Load the next batch automatically with a lazy Turbo Frame.',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'loading', 'scroll'],
                longDescription: <<<EOF
                    Building on [Part One](/demos/turbo/infinite-scroll), this version loads the next batch when a `lazy Turbo Frame` enters the scrollable catalog; compare it with the [Live Component version](/demos/live-component/infinite-scroll-2), which uses a Live Component to load on scroll.
                    EOF,
            ),
            new TurboDemo(
                'infinite-scroll',
                name: 'Turbo Infinite Scroll - 1/2',
                description: 'Append a batch of cards with a link and two Turbo Streams.',
                author: 'ker0x',
                publishedAt: '2026-04-20',
                tags: ['grid', 'pagination', 'navigation'],
                longDescription: <<<EOF
                    This demo explores the same product grid as the [Live Component version](/demos/live-component/infinite-scroll), using `Turbo Streams`: click `Load More` to append a batch of cards and replace the link with the next page.
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
