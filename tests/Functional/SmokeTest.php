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

use App\Entity\Food;
use App\Model\Demo;
use App\Model\UxPackage;
use App\Service\DemoRepository;
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\Persistence\persist;

class SmokeTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    #[Before]
    public function setupEntities(): void
    {
        persist(Food::class, ['name' => 'Pizza', 'votes' => 10]);
    }

    #[DataProvider('provideStaticUrls')]
    public function testStaticPages(string $url): void
    {
        $this->browser()
            ->visit($url)
            ->assertSuccessful()
        ;
    }

    public static function provideStaticUrls(): \Generator
    {
        yield 'homepage' => ['/'];
        yield 'documentation' => ['/documentation'];
        yield 'changelog' => ['/changelog'];
        yield 'packages' => ['/packages'];
        yield 'demos' => ['/demos'];
        yield 'support' => ['/support'];
        yield 'robots.txt' => ['/robots.txt'];
        yield 'sitemap.xml' => ['/sitemap.xml'];
    }

    public function testHomepageListsUiKits(): void
    {
        $page = $this->browser()
            ->visit('/')
            ->assertSuccessful()
            ->assertSee('Browse all UI Kits')
        ;

        self::assertSame('Or start from a whole UI kit.', $page->crawler()->filter('#homepage-ui-kits-title')->text());
        self::assertSame(
            ['Shadcn UI', 'Common', 'Bootstrap', 'Flowbite v4'],
            $page->crawler()->filter('[aria-labelledby="homepage-ui-kits-title"] h3 a[href*="/toolkit/kits/"]')->each(static fn ($node): string => $node->text()),
        );
        self::assertSame(1, $page->crawler()->filter('[aria-labelledby="homepage-ui-kits-title"] a[href="/kits"]')->count());
    }

    public function testHomepageFeaturesPagination(): void
    {
        $page = $this->browser()
            ->visit('/')
            ->assertSuccessful()
        ;

        self::assertSame(1, $page->crawler()->filter('main h3 a[href$="/pagination"]')->count());
    }

    #[DataProvider('providePackageUrls')]
    public function testPackagePages(UxPackage $package): void
    {
        $this->browser()
            ->visit('/'.$package->getName())
            ->assertSuccessful()
        ;
    }

    public static function providePackageUrls(): \Generator
    {
        $repository = new UxPackageRepository();
        foreach ($repository->findAll(removed: false) as $package) {
            yield $package->getName() => [$package];
        }
    }

    #[DataProvider('provideDemoUrls')]
    public function testDemoPages(Demo $demo): void
    {
        $router = self::bootKernel()->getContainer()->get('router');
        $url = $router->generate($demo->getRoute());

        $this->browser()
            ->visit($url)
            ->assertSuccessful()
        ;
    }

    public static function provideDemoUrls(): \Generator
    {
        $repository = new DemoRepository();
        foreach ($repository->findAll() as $demo) {
            yield $demo->getPackage().'-'.$demo->getIdentifier() => [$demo];
        }
    }

    #[TestWith(['Turbo Infinite Scroll - 1/2', '/demos/turbo/infinite-scroll', '/demos/turbo/infinite-scroll-2', '/demos/live-component/infinite-scroll'])]
    #[TestWith(['Turbo Infinite Scroll - 2/2', '/demos/turbo/infinite-scroll-2', '/demos/turbo/infinite-scroll', '/demos/live-component/infinite-scroll-2'])]
    public function testTurboScrollLinksToItsRelatedDemos(string $title, string $url, string $otherPart, string $liveDemo): void
    {
        $this->browser()
            ->visit($url)
            ->assertSuccessful()
            ->assertSeeIn('h1', $title)
            ->assertElementCount(\sprintf('.demo-introduction a[href="%s"]', $liveDemo), 1)
            ->assertElementCount('aside[aria-label="Related demos"] .Card', 2)
            ->assertElementCount(\sprintf('aside[aria-label="Related demos"] .Card a[href="%s"]', $otherPart), 1)
            ->assertElementCount(\sprintf('aside[aria-label="Related demos"] .Card a[href="%s"]', $liveDemo), 1)
        ;
    }

    public function testTurboInfiniteScrollPartTwoLinksToPartOneInItsIntro(): void
    {
        $this->browser()
            ->visit('/demos/turbo/infinite-scroll-2')
            ->assertSuccessful()
            ->assertSeeIn('.demo-introduction a[href="/demos/turbo/infinite-scroll"]', 'Part One')
        ;
    }

    public function testLiveInfiniteScrollPartOneHasStructuredExplanationSections(): void
    {
        $this->browser()
            ->visit('/demos/live-component/infinite-scroll')
            ->assertSuccessful()
            ->assertSee('So... how does it work?')
            ->assertSee('Request the next batch')
            ->assertSee('Keep earlier cards in place')
            ->assertElementCount('main section.bg-subtle.border-t', 1)
        ;
    }

    public function testLiveInfiniteScrollPartTwoLinksToPartOneInItsIntro(): void
    {
        $this->browser()
            ->visit('/demos/live-component/infinite-scroll-2')
            ->assertSuccessful()
            ->assertSeeIn('.demo-introduction a[href="/demos/live-component/infinite-scroll"]', 'Part One')
        ;
    }
}
