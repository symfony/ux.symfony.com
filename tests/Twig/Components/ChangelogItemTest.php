<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Components;

use App\Twig\Components\ChangelogItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChangelogItemTest extends TestCase
{
    public function testSetItem(): void
    {
        $component = new ChangelogItem();
        $component->item = [
            'id' => 1,
            'name' => 'Test',
            'version' => 'v1.0.0',
            'date' => '2024-01-01',
            'body' => 'foobar',
        ];

        $this->assertSame('foobar', $component->getContent());
    }

    /**
     * @param array{id: int, name?: string, version: string, date: string} $item
     */
    #[DataProvider('provideTitleValues')]
    public function testGetTitle(array $item, string $expected): void
    {
        $component = new ChangelogItem();
        $component->item = $item;

        $this->assertSame($expected, $component->getTitle());
    }

    /**
     * @return iterable<string, array{0: array{id: int, name?: string, version: string, date: string}, 1: string}>
     */
    public static function provideTitleValues(): iterable
    {
        yield 'name_after_version' => [
            ['id' => 1, 'name' => 'v3.4.0: Toolkit!', 'version' => 'v3.4.0', 'date' => '2026-01-01'],
            'Toolkit!',
        ];
        yield 'name_without_version' => [
            ['id' => 1, 'name' => 'Symfony 8!', 'version' => 'v3.0.0', 'date' => '2026-01-01'],
            'Symfony 8!',
        ];
        yield 'name_is_version' => [
            ['id' => 1, 'name' => 'v3.5.1', 'version' => 'v3.5.1', 'date' => '2026-01-01'],
            '',
        ];
        yield 'no_name' => [
            ['id' => 1, 'version' => 'v3.5.1', 'date' => '2026-01-01'],
            '',
        ];
    }

    #[DataProvider('provideContentValues')]
    public function testFormatContent(string $body, string $expected): void
    {
        $component = new ChangelogItem();
        $component->item = [
            'id' => 1,
            'name' => 'Test',
            'version' => 'v1.0.0',
            'date' => '2024-01-01',
            'body' => $body,
        ];

        $this->assertSame($expected, $component->getContent());
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideContentValues(): iterable
    {
        yield 'keep_existing_h1' => [
            '# Title 1',
            '# Title 1',
        ];
        yield 'transform_h2_to_h3' => [
            '## Title',
            '### Title',
        ];
        yield 'keep_existing_h3' => [
            '### Title 3',
            '### Title 3',
        ];
        yield 'inject_changelog_link' => [
            'https://github.com/symfony/ux/compare/v2.14.1...v2.14.2',
            '[v2.14.1 -> v2.14.2](https://github.com/symfony/ux/compare/v2.14.1...v2.14.2)',
        ];
    }
}
