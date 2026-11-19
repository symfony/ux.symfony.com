<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Model\Demo;
use App\Model\LiveDemo;
use App\Model\TurboDemo;
use App\Model\UxPackage;
use App\Service\DemoRepository;
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DemoRepository::class)]
final class DemoRepositoryTest extends TestCase
{
    public function testEveryDemoShowcasesAListedPackage(): void
    {
        $packages = array_map(static fn (UxPackage $package): string => $package->getName(), new UxPackageRepository()->findAll(removed: false));

        foreach (new DemoRepository()->findAll() as $demo) {
            self::assertContains($demo->getPackage(), $packages, \sprintf('Demo "%s" showcases an unknown package.', $demo->getName()));
        }
    }

    public function testEveryDemoHasAUniqueIdentifierInItsPackage(): void
    {
        $keys = array_map(static fn (Demo $demo): string => $demo->getPackage().'/'.$demo->getIdentifier(), new DemoRepository()->findAll());

        self::assertSame(array_unique($keys), $keys);
    }

    public function testFindByPackageKeepsTheDemosOfThatPackageInOrder(): void
    {
        $demos = new DemoRepository()->findByPackage('pagination');

        self::assertSame(
            ['pagination', 'cursor-pagination', 'pagination-themes'],
            array_map(static fn (Demo $demo): string => $demo->getIdentifier(), $demos),
        );
    }

    public function testFindTellsApartDemosSharingAnIdentifier(): void
    {
        $repository = new DemoRepository();

        self::assertInstanceOf(TurboDemo::class, $repository->find('turbo', 'infinite-scroll'));
        self::assertInstanceOf(LiveDemo::class, $repository->find('live-component', 'infinite-scroll'));
    }

    public function testFindThrowsForAnUnknownDemo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Unknown "turbo" demo "voting".');

        new DemoRepository()->find('turbo', 'voting');
    }

    public function testPreviousAndNextStayInThePackageOfTheDemo(): void
    {
        $repository = new DemoRepository();

        foreach ($repository->findAll() as $demo) {
            foreach ([$repository->getPrevious($demo, loop: true), $repository->getNext($demo, loop: true)] as $sibling) {
                self::assertNotNull($sibling);
                self::assertSame($demo->getPackage(), $sibling->getPackage(), \sprintf('Demo "%s" leads to a demo of another package.', $demo->getName()));
            }
        }
    }
}
