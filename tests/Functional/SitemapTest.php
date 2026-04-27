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

use App\Service\LiveDemoRepository;
use App\Service\TurboDemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Browser\Test\HasBrowser;

class SitemapTest extends KernelTestCase
{
    use HasBrowser;

    public function testSitemapContainsPages()
    {
        $browser = $this->browser()
            ->visit('/sitemap.xml')
            ->assertSuccessful()
            ->assertXml()
        ;

        $sitemap = $browser->content();
        foreach ($this->getSmokeTests() as $url) {
            $this->assertStringContainsString('<loc>'.$url.'</loc>', $sitemap);
        }
    }

    private function getSmokeTests(): \Generator
    {
        $router = self::bootKernel()->getContainer()->get('router');

        $liveDemoRepository = new LiveDemoRepository();
        foreach ($liveDemoRepository->findAll() as $demo) {
            yield $demo->getRoute() => $router->generate($demo->getRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $turboDemoRepository = new TurboDemoRepository();
        foreach ($turboDemoRepository->findAll() as $demo) {
            yield $demo->getRoute() => $router->generate($demo->getRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $packageRepository = new UxPackageRepository();
        foreach ($packageRepository->findAll(removed: false) as $package) {
            yield $package->getRoute() => $router->generate($package->getRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
