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

    public function testCanRenderIconSvg()
    {
        $this->browser()
            ->visit('/icon/lucide/a-arrow-down.svg')
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'image/svg+xml')
            ->assertContains('viewBox="0 0 24 24"')
            ->assertContains('<path')
        ;
    }

    public function testIconSvgIsPubliclyCacheable()
    {
        $this->browser()
            ->visit('/icon/lucide/a-arrow-down.svg')
            ->assertSuccessful()
            ->assertHeaderContains('Cache-Control', 'public')
            ->assertHeaderContains('Cache-Control', 'max-age=604800')
            // a session cookie here would make Cloudflare bypass its cache
            ->use(function (KernelBrowser $client) {
                $this->assertFalse($client->getResponse()->headers->has('Set-Cookie'));
            })
        ;
    }

    public function testRenderingAnUnknownIconIs404()
    {
        $this->browser()
            ->visit('/icon/lucide/not-a-real-icon.svg')
            ->assertStatus(404)
        ;
    }

    public function testIconSvgRouteRejectsAnInvalidName()
    {
        $this->browser()
            ->visit('/icon/lucide/A-Arrow-Down.svg')
            ->assertStatus(404)
            ->visit('/icon/lucide/a-arrow-down.png')
            ->assertStatus(404)
        ;
    }
}
