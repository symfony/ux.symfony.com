<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Model;

use App\Model\UxPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UxPackage::class)]
final class UxPackageTest extends TestCase
{
    public function testMetaTitlesUsePackageDefaults(): void
    {
        $package = new UxPackage(
            'foo-bar',
            'FooBar',
            'app_foo_bar',
            '#000000',
            'Lorem Ipsum',
            'Lorem ipsum dolor sit amet.',
            new \DateTimeImmutable('2026-01-01'),
        );

        self::assertSame('FooBar - Lorem Ipsum', $package->getSeoTitle());
        self::assertSame('Lorem Ipsum - Symfony UX FooBar', $package->getSocialTitle());
    }

    public function testMetaTitlesCanBeOverridden(): void
    {
        $package = new UxPackage(
            'foo-bar',
            'FooBar',
            'app_foo_bar',
            '#000000',
            'Lorem Ipsum',
            'Lorem ipsum dolor sit amet.',
            new \DateTimeImmutable('2026-01-01'),
            seoTitle: 'Lorem Ipsum - FooBar',
            socialTitle: 'FooBar - Lorem Ipsum',
        );

        self::assertSame('Lorem Ipsum - FooBar', $package->getSeoTitle());
        self::assertSame('FooBar - Lorem Ipsum', $package->getSocialTitle());
    }

    #[DataProvider('provideIsNew')]
    public function testIsNew(\DateTimeImmutable $publishedAt, bool $expected): void
    {
        $package = new UxPackage(
            'foo-bar',
            'FooBar',
            'app_foo_bar',
            '#000000',
            'Lorem Ipsum',
            'Lorem ipsum dolor sit amet.',
            $publishedAt,
        );

        self::assertSame($expected, $package->isNew());
    }

    public static function provideIsNew(): \Generator
    {
        yield 'published 5 months ago' => [new \DateTimeImmutable('-5 months'), true];
        yield 'published 7 months ago' => [new \DateTimeImmutable('-7 months'), false];
    }
}
