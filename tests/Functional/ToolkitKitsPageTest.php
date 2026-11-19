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

use App\Service\Toolkit\ToolkitService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Test\HasBrowser;

final class ToolkitKitsPageTest extends KernelTestCase
{
    use HasBrowser;

    public function testTheKitsPageListsEveryKit(): void
    {
        $page = $this->browser()
            ->visit('/kits')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'UI Kits')
        ;

        $expected = [];
        foreach (self::getContainer()->get(ToolkitService::class)->getKits() as $kitId => $kit) {
            $expected[] = ['/toolkit/kits/'.$kitId, $kit->manifest->name];
        }

        self::assertNotEmpty($expected);
        self::assertSame(
            $expected,
            $page->crawler()->filter('main h2 a')->each(static fn (Crawler $link): array => [$link->attr('href'), $link->text()]),
        );
    }

    public function testTheKitsPageIsTitledUiKitsForSearchAndSocialNetworks(): void
    {
        $crawler = $this->browser()
            ->visit('/kits')
            ->assertSuccessful()
            ->crawler()
        ;

        self::assertSame('UI Kits - Symfony UX', $crawler->filter('title')->text());
        self::assertSame('UI Kits: Shadcn UI, Bootstrap, Flowbite - Symfony UX', $crawler->filter('meta[property="og:title"]')->attr('content'));
        self::assertSame('UI Kits: Shadcn UI, Bootstrap, Flowbite - Symfony UX', $crawler->filter('meta[name="twitter:title"]')->attr('content'));

        $description = 'UI kits for Symfony: Twig components for Shadcn UI, Bootstrap and Flowbite, installed one by one with UX Toolkit.';
        self::assertSame($description, $crawler->filter('meta[name="description"]')->attr('content'));
        self::assertSame($description, $crawler->filter('meta[property="og:description"]')->attr('content'));
        self::assertSame($description, $crawler->filter('meta[name="twitter:description"]')->attr('content'));
        self::assertSame('Toolkit - UI Kits', $crawler->filter('meta[property="og:image:alt"]')->attr('content'));
    }

    public function testTheKitsPageShowsHowToolkitInstallsAComponent(): void
    {
        $page = $this->browser()
            ->visit('/kits')
            ->assertSuccessful()
        ;

        $section = $page->crawler()->filter('main section[aria-labelledby="kits-toolkit-title"]');

        self::assertSame('Installed with UX Toolkit.', $section->filter('#kits-toolkit-title')->text());
        self::assertStringContainsString('composer require --dev symfony/ux-toolkit', $section->text());
        self::assertStringContainsString('php bin/console ux:install button --kit=shadcn', $section->text());
        self::assertSame(1, $section->filter('a[href="/toolkit"]')->count());
    }

    public function testTheKitsPagePointsToCommunityKits(): void
    {
        $page = $this->browser()
            ->visit('/kits')
            ->assertSuccessful()
        ;

        $section = $page->crawler()->filter('main section[aria-labelledby="kits-community-title"]');

        self::assertSame('Kits from the community.', $section->filter('#kits-community-title')->text());
        self::assertStringContainsString('php bin/console ux:install button --kit=github.com/', $section->text());
        self::assertSame(1, $section->filter('a[href="https://github.com/topics/symfony-ux-toolkit"]')->count());
    }

    public function testAKitPageLinksBackToTheKitsPage(): void
    {
        $page = $this->browser()
            ->visit('/toolkit/kits/shadcn')
            ->assertSuccessful()
        ;

        self::assertSame('Kits', $page->crawler()->filter('main a[href="/kits"]')->text());
    }

    public function testTheFormerAddressRedirectsToTheKitsPage(): void
    {
        $this->browser()
            ->interceptRedirects()
            ->visit('/toolkit/kits')
            ->assertStatus(301)
            ->assertRedirectedTo('/kits')
        ;
    }
}
