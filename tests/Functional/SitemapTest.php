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

use App\Enum\PackageGroup;
use App\Service\LiveDemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Browser\Test\HasBrowser;

class SitemapTest extends KernelTestCase
{
    use HasBrowser;

    public function testSitemapContainsPages(): void
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

        yield 'pagination_demos' => $router->generate('app_demo_pagination', [], UrlGeneratorInterface::ABSOLUTE_URL);
        yield 'upload_demos' => $router->generate('app_demo_upload', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $demoRepository = new LiveDemoRepository();
        foreach ($demoRepository->findAll() as $demo) {
            yield $demo->getRoute() => $router->generate($demo->getRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        foreach (PackageGroup::cases() as $group) {
            yield 'packages '.$group->value => $router->generate('app_packages_group', ['group' => $group->value], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $packageRepository = new UxPackageRepository();
        foreach ($packageRepository->findAll(removed: false) as $package) {
            yield $package->getRoute() => $router->generate($package->getRoute(), [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
    }
}
