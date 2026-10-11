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
}
