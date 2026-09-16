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

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconsTest extends KernelTestCase
{
    use HasBrowser;

    public function testCanViewIconFromHomepage()
    {
        $this->browser()
            ->visit('/')
            ->assertSuccessful()
            ->assertSeeIn('header', 'Icons')
            ->click('Icons')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Icons')
            ->assertSeeIn('h1', 'Icons')
        ;
    }

    public function testCanViewIconIndex()
    {
        $this->browser()
            ->visit('/icons')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Icons')
        ;
    }

    public function testIconsAreRenderedByTheWebComponentNotIconifysSvgEndpoint()
    {
        $this->browser()
            ->visit('/icons?set=lucide')
            ->assertSuccessful()
            ->use(function (KernelBrowser $client) {
                $html = $client->getResponse()->getContent();

                // that endpoint rate limits an IP after ~65 requests; the component
                // uses Iconify's batched JSON API instead
                $this->assertSame(0, preg_match_all('#api\\.iconify\\.design/[a-z0-9-]+/#', $html));
                $this->assertGreaterThan(0, substr_count($html, '<iconify-icon'));
            })
        ;
    }
}
