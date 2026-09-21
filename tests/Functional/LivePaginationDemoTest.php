<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class LivePaginationDemoTest extends KernelTestCase
{
    use HasBrowser;

    public function testDemoRendersPaginatedJerseys(): void
    {
        $page = $this->browser()
            ->visit('/demos/pagination/live-pagination')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Live Pagination')
            ->assertSee('120 jerseys are ready')
            ->assertSee('Total jerseys')
        ;

        self::assertSame(10, $page->crawler()->filter('.LivePagination .JerseyCard')->count());
        self::assertSame(6, $page->crawler()->filter('.LivePagination .filter.colors button')->count());
        self::assertSame(5, $page->crawler()->filter('.LivePagination .filter.patterns button')->count());
        self::assertSame(1, $page->crawler()->filter('.LivePagination nav')->count());
        self::assertStringEndsWith('/demos/pagination/live-pagination', $page->crawler()->filter('link[rel="canonical"]')->attr('href'));
    }

    public function testDemoIsLinkedFromPaginationListings(): void
    {
        foreach (['/pagination', '/demos/pagination'] as $url) {
            $page = $this->browser()->visit($url)->assertSuccessful();

            self::assertGreaterThanOrEqual(1, $page->crawler()->filter('a[href="/demos/pagination/live-pagination"]')->count());
        }
    }

    public function testDemoIsInTheSitemap(): void
    {
        $page = $this->browser()->visit('/sitemap.xml')->assertSuccessful();

        self::assertStringContainsString('/demos/pagination/live-pagination</loc>', $page->content());
    }
}
