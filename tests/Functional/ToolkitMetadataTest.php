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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class ToolkitMetadataTest extends KernelTestCase
{
    use HasBrowser;

    #[DataProvider('provideToolkitPages')]
    public function testPageUrlMetadata(string $path): void
    {
        $page = $this->browser()
            ->visit($path)
            ->assertSuccessful()
        ;

        $expectedUrl = 'http://localhost'.$path;

        self::assertSame($expectedUrl, $page->crawler()->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame($expectedUrl, $page->crawler()->filter('meta[property="og:url"]')->attr('content'));
        self::assertSame($expectedUrl, $page->crawler()->filter('meta[name="twitter:url"]')->attr('content'));
    }

    public static function provideToolkitPages(): \Generator
    {
        yield 'kit list' => ['/kits'];
        yield 'kit' => ['/toolkit/kits/shadcn'];
        yield 'component list' => ['/toolkit/kits/shadcn/components'];
        yield 'component' => ['/toolkit/kits/shadcn/components/accordion'];
        yield 'block list' => ['/toolkit/kits/shadcn/blocks'];
        yield 'block section' => ['/toolkit/kits/shadcn/blocks/login'];
    }
}
