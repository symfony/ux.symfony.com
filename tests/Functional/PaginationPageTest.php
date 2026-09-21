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

final class PaginationPageTest extends KernelTestCase
{
    use HasBrowser;

    public function testPackagePage(): void
    {
        $page = $this->browser()
            ->visit('/pagination')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Pagination for all.')
            ->assertSeeIn('h1 strong', 'page ahead')
            ->assertSee('composer require symfony/ux-pagination')
            ->assertSee('Any source')
            ->assertSee('Pages or cursors')
            ->assertSee('Symfony native')
            ->assertSee('Made for Twig')
            ->assertSee('Live-ready')
            ->assertSee('Configure by context')
            ->assertSee('Inject with #[Target]')
            ->assertSee('Test the real contract')
            ->assertSee('See every page in action.')
        ;

        self::assertSame('Symfony UX Pagination - Pages, Lookahead & Cursors', $page->crawler()->filter('title')->text());
        self::assertStringEndsWith('/pagination', $page->crawler()->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame('website', $page->crawler()->filter('meta[property="og:type"]')->attr('content'));
        self::assertSame('Symfony UX Pagination', $page->crawler()->filter('meta[property="og:image:alt"]')->attr('content'));
        self::assertSame('Symfony UX Pagination', $page->crawler()->filter('meta[name="twitter:image:alt"]')->attr('content'));
        self::assertSame(1, $page->crawler()->filter('.PackageHeader a[href="/demos/pagination"]')->count());
        self::assertSame(1, $page->crawler()->filter('[data-pagination-feature="live-component"] a[href="#"]')->count());
        self::assertSame(3, $page->crawler()->filter('[data-pagination-demo-list] .Card')->count());
        self::assertSame(3, $page->crawler()->filter('[data-pagination-demo-list] .Card a')->count());
        self::assertSame(0, $page->crawler()->filter('[data-pagination-demo-list] .Tag')->count());
    }

    public function testDemosIndex(): void
    {
        $page = $this->browser()
            ->visit('/demos/pagination')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Pagination demos')
            ->assertSee('Live Pagination')
            ->assertSee('Cursor Pagination')
            ->assertSee('Pagination Themes')
        ;

        self::assertSame(3, $page->crawler()->filter('.DemoCardGrid .Card')->count());
        self::assertSame(3, $page->crawler()->filter('.DemoCardGrid .Card a')->count());
        self::assertGreaterThan(0, $page->crawler()->filter('.DemoCardGrid .Tag')->count());
        self::assertSame('Pagination demos - Symfony UX', $page->crawler()->filter('title')->text());
    }

    public function testPaginationDemosAreDiscoverable(): void
    {
        $page = $this->browser()
            ->visit('/demos')
            ->assertSuccessful()
        ;

        self::assertSame(1, $page->crawler()->filter('.Card a[href="/demos/pagination"]')->count());
    }
}
