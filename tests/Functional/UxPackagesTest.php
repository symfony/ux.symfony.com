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
use App\Model\UxPackage;
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class UxPackagesTest extends KernelTestCase
{
    use HasBrowser;

    public function testAllPackagesPage(): void
    {
        $this->browser()
            ->visit('/packages')
            ->assertSuccessful()
            ->assertSeeIn('title', 'Packages')
            ->assertSee('All Packages')
        ;
    }

    public function testThePackagesPageOffersEveryGroup(): void
    {
        $pills = $this->browser()->visit('/packages')->assertSuccessful()->crawler()->filter('nav[aria-label="Package groups"] a');

        self::assertSame(
            ['All', ...array_map(static fn (PackageGroup $group): string => $group->label(), PackageGroup::cases())],
            $pills->each(static fn ($pill) => $pill->text()),
        );
        self::assertSame(['/packages'], $pills->filter('[aria-current="page"]')->each(static fn ($pill) => $pill->attr('href')));
    }

    #[DataProvider('provideGroups')]
    public function testAGroupPageListsItsPackages(PackageGroup $group): void
    {
        $crawler = $this->browser()
            ->visit('/packages/'.$group->value)
            ->assertSuccessful()
            ->assertSeeIn('title', $group->label())
            ->assertSeeIn('h1', $group->label())
            ->crawler()
        ;

        $expected = array_map(static fn (UxPackage $package): string => $package->getHumanName(), new UxPackageRepository()->findByGroup($group));
        self::assertNotEmpty($expected);
        self::assertSame($expected, $crawler->filter('#packages h2 a')->each(static fn ($link) => $link->text()));
        self::assertSame(['/packages/'.$group->value], $crawler->filter('nav[aria-label="Package groups"] [aria-current="page"]')->each(static fn ($pill) => $pill->attr('href')));
        self::assertStringEndsWith('/packages/'.$group->value, (string) $crawler->filter('link[rel="canonical"]')->attr('href'));
    }

    /**
     * @return iterable<string, array{PackageGroup}>
     */
    public static function provideGroups(): iterable
    {
        foreach (PackageGroup::cases() as $group) {
            yield $group->value => [$group];
        }
    }

    public function testAnUnknownGroupIsNotFound(): void
    {
        $this->browser()->visit('/packages/no-such-group')->assertStatus(404);
    }

    #[DataProvider('getSmokeTests')]
    public function testPackagePagesAllLoad(UxPackage $package, string $expectedText): void
    {
        $this->browser()
            ->throwExceptions()
            ->visit('/'.$package->getName())
            ->assertSuccessful()
            ->assertSeeIn('title', $package->getHumanName())
            ->assertSee($expectedText)
        ;
    }

    public static function getSmokeTests(): \Generator
    {
        $repository = new UxPackageRepository();
        foreach ($repository->findAll(removed: false) as $package) {
            if ('live-component' === $package->getName()) {
                // Live Component has a different bottom section
                yield $package->getName() => [$package, 'Read documentation'];
                continue;
            }
            if ('icons' === $package->getName()) {
                // Icons has a different bottom section
                yield $package->getName() => [$package, 'Documentation'];
                continue;
            }

            yield $package->getName() => [$package, \sprintf('%s Doc', $package->getHumanName())];
        }
    }
}
