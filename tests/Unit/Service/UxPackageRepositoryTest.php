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

use App\Model\UxPackage;
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UxPackageRepository::class)]
final class UxPackageRepositoryTest extends TestCase
{
    public function testFindLatestNewReturnsNewPackagesFromNewestToOldest(): void
    {
        $packages = new UxPackageRepository()->findLatestNew(3);

        self::assertLessThanOrEqual(3, \count($packages));

        $previous = null;
        foreach ($packages as $package) {
            self::assertTrue($package->isNew(), \sprintf('Package "%s" is not new.', $package->getName()));
            self::assertFalse($package->isRemoved(), \sprintf('Package "%s" is removed.', $package->getName()));

            if (null !== $previous) {
                self::assertGreaterThanOrEqual($package->getPublishedAt(), $previous->getPublishedAt());
            }
            $previous = $package;
        }
    }

    public function testEveryListedPackageNamesThreeOtherListedPackages(): void
    {
        $repository = new UxPackageRepository();
        $listed = array_map(static fn (UxPackage $package): string => $package->getName(), $repository->findAll(removed: false));

        foreach ($repository->findAll(removed: false) as $package) {
            $related = $package->getRelated();

            self::assertCount(3, array_unique($related), \sprintf('Package "%s" must name three different related packages.', $package->getName()));
            self::assertNotContains($package->getName(), $related, \sprintf('Package "%s" relates to itself.', $package->getName()));
            foreach ($related as $name) {
                self::assertContains($name, $listed, \sprintf('Package "%s" relates to "%s", which is unknown or removed.', $package->getName(), $name));
            }
        }
    }

    public function testFindRelatedReturnsThePackagesInTheirOrder(): void
    {
        $repository = new UxPackageRepository();
        $related = $repository->findRelated($repository->find('turbo'));

        self::assertSame(['live-component', 'stimulus', 'inspector'], array_map(static fn (UxPackage $package): string => $package->getName(), $related));
    }
}
