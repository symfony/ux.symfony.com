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
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Test\HasBrowser;

final class ImagePageTest extends KernelTestCase
{
    use HasBrowser;

    public function testPackagePage(): void
    {
        $this->browser()
            ->visit('/image')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'transformed by your CDN')
            ->assertSee('composer require symfony/ux-image')
            ->assertSee('UX_IMAGE_DSN=cloudflare://')
            ->assertSee('UX_IMAGE_DSN=cloudinary://')
            ->assertSee('UX_IMAGE_DSN=imgix://')
            ->assertSee('UX_IMAGE_DSN=keycdn://')
        ;
    }

    public function testThumbnailPresetIsAppliedToADemoImage(): void
    {
        $crawler = $this->browser()
            ->visit('/image')
            ->assertSuccessful()
            ->crawler();

        $thumbnails = $crawler->filter('#demo [data-image-demo] img')->reduce(
            static fn (Crawler $image): bool => 1 === preg_match('#/w_200,h_200,[^/]*\bq_60\b#', (string) $image->attr('src')),
        );

        self::assertCount(1, $thumbnails);
    }

    public function testDemoImagesAreDeliveredByTheProvider(): void
    {
        $crawler = $this->browser()
            ->visit('/image')
            ->assertSuccessful()
            ->crawler();

        $images = $crawler->filter('#demo [data-image-demo] img');
        self::assertCount(3, $images);

        $images->each(static function (Crawler $image): void {
            $candidates = explode(', ', (string) $image->attr('srcset'));
            self::assertGreaterThan(1, \count($candidates));

            foreach ([(string) $image->attr('src'), ...$candidates] as $url) {
                self::assertMatchesRegularExpression('#^https://res\.cloudinary\.com/ux-test/image/fetch/\S+/https://ux\.symfony\.com/uploads/#', $url);
            }
        });
    }

    public function testDemoShowsTheGeneratedHtmlAndTheOriginalWeight(): void
    {
        $crawler = $this->browser()
            ->visit('/image')
            ->assertSuccessful()
            ->crawler();

        $demos = $crawler->filter('#demo [data-image-demo]');
        self::assertCount(3, $demos);

        $demos->each(static function (Crawler $demo): void {
            self::assertStringStartsWith('<twig:ux:image', $demo->filter('pre')->first()->text());
            self::assertStringContainsString('srcset=', $demo->filter('pre')->last()->text());
            self::assertMatchesRegularExpression('/\d(\.\d)? MB JPEG/', $demo->filter('[data-image-demo-original]')->text());
        });
    }
}
