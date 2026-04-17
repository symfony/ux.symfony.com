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
use App\Model\LiveDemo;
use App\Model\UxPackage;
use App\Service\LiveDemoRepository;
use App\Service\UxPackageRepository;
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

    /**
     * @before
     */
    public function setupEntities(): void
    {
        persist(Food::class, ['name' => 'Pizza', 'votes' => 10]);
    }

    /**
     * @dataProvider provideStaticUrls
     */
    public function testStaticPages(string $url)
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

    /**
     * @dataProvider providePackageUrls
     */
    public function testPackagePages(UxPackage $package)
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

    /**
     * @dataProvider provideDemoUrls
     */
    public function testDemoPages(LiveDemo $demo)
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
        $repository = new LiveDemoRepository();
        foreach ($repository->findAll() as $demo) {
            yield $demo->getIdentifier() => [$demo];
        }
    }
}
