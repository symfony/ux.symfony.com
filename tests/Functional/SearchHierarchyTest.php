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
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class SearchHierarchyTest extends KernelTestCase
{
    use HasBrowser;

    /**
     * @param list<string> $expectedHierarchy
     */
    #[DataProvider('providePages')]
    public function testPageDeclaresItsSearchHierarchy(string $path, array $expectedHierarchy): void
    {
        $page = $this->browser()
            ->visit($path)
            ->assertSuccessful()
        ;

        $hierarchy = $page->crawler()
            ->filter('meta[name="search:hierarchy"]')
            ->each(static fn ($meta) => $meta->attr('content'))
        ;

        self::assertSame($expectedHierarchy, $hierarchy);
    }

    public function testPackageSuggestionsAreIgnoredBySearch(): void
    {
        $this->browser()
            ->visit('/live-component')
            ->assertSuccessful()
            ->assertSeeIn('[data-search-ignore]', 'More from Symfony UX')
        ;
    }

    #[DataProvider('providePackages')]
    public function testAPackagePageSuggestsItsRelatedPackages(string $name): void
    {
        $repository = new UxPackageRepository();
        $package = $repository->find($name);
        $expected = array_map(static fn ($related) => $related->getHumanName(), $repository->findRelated($package));

        $suggestions = $this->browser()
            ->visit(self::getContainer()->get('router')->generate($package->getRoute()))
            ->assertSuccessful()
            ->crawler()
            ->filter('[data-search-ignore] h3 a')
            ->each(static fn ($link) => $link->text())
        ;

        self::assertCount(3, $expected);
        self::assertSame($expected, $suggestions);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providePackages(): iterable
    {
        foreach (new UxPackageRepository()->findAll(removed: false) as $package) {
            yield $package->getName() => [$package->getName()];
        }
    }

    public function testKitComponentInstallationIsIgnoredBySearch(): void
    {
        $this->browser()
            ->visit('/toolkit/kits/shadcn/components/tooltip')
            ->assertSuccessful()
            ->assertSeeIn('#content-installation + [data-search-ignore]', 'Available since UX Toolkit')
            ->assertNotSeeIn('[data-search-ignore]', 'Usage')
        ;
    }

    public static function providePages(): \Generator
    {
        foreach ((new UxPackageRepository())->findAll(removed: false) as $package) {
            yield 'package '.$package->getName() => ['/'.$package->getName(), ['Packages', $package->getHumanName()]];
        }

        yield 'live component demo' => ['/demos/live-component/voting', ['Demos', 'Live Components', 'Up & Down Voting']];
        yield 'live memory demo' => ['/demos/live-memory/', ['Demos', 'Live Components', 'Live Memory Card Game']];
        yield 'pagination demo' => ['/demos/pagination/themes', ['Demos', 'Pagination', 'Pagination Themes']];

        yield 'kit' => ['/toolkit/kits/shadcn', ['Kits', 'Shadcn UI']];
        yield 'kit component list' => ['/toolkit/kits/shadcn/components', ['Kits', 'Shadcn UI', 'Components']];
        yield 'kit component' => ['/toolkit/kits/shadcn/components/button', ['Kits', 'Shadcn UI', 'Components', 'Button']];
        yield 'kit block list' => ['/toolkit/kits/shadcn/blocks', ['Kits', 'Shadcn UI', 'Blocks']];
        yield 'kit block section' => ['/toolkit/kits/shadcn/blocks/login', ['Kits', 'Shadcn UI', 'Blocks', 'Login']];

        yield 'homepage' => ['/', ['Resources', 'Symfony UX']];
        yield 'documentation' => ['/documentation', ['Resources', 'Documentation']];
        yield 'support' => ['/support', ['Resources', 'Support']];
        yield 'changelog' => ['/changelog', ['Resources', 'Changelog']];
        yield 'cookbook' => ['/cookbook', ['Resources', 'Cookbook']];
        yield 'cookbook recipe' => ['/cookbook/component-architecture', ['Resources', 'Cookbook', 'Component Architecture']];

        yield 'package list' => ['/packages', []];
        foreach (PackageGroup::cases() as $group) {
            yield 'package group '.$group->value => ['/packages/'.$group->value, ['Packages', $group->label()]];
        }
        yield 'demo list' => ['/demos', []];
        yield 'pagination demo list' => ['/demos/pagination', []];
    }
}
