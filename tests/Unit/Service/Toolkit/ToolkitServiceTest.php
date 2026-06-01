<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service\Toolkit;

use App\Service\Toolkit\ToolkitService;
use PHPUnit\Framework\TestCase;

class ToolkitServiceTest extends TestCase
{
    /**
     * @dataProvider provideDescriptionsWithDefault
     */
    public function testExtractPropDefault(string $description, string $expectedDescription, ?string $expectedDefault)
    {
        $this->assertSame(
            ['description' => $expectedDescription, 'default' => $expectedDefault],
            ToolkitService::extractPropDefault($description),
        );
    }

    public static function provideDescriptionsWithDefault(): iterable
    {
        yield 'a regular default value' => [
            'The visual style variant. Defaults to `brand`',
            'The visual style variant.',
            'brand',
        ];

        yield 'a boolean default value' => [
            'Whether the alert can be dismissible. Defaults to `false`',
            'Whether the alert can be dismissible.',
            'false',
        ];

        yield 'an empty default value' => [
            'Define the open Tabs at initial rendering. Defaults to ``',
            'Define the open Tabs at initial rendering.',
            '',
        ];

        yield 'no default value' => [
            'A description without any default value',
            'A description without any default value',
            null,
        ];
    }
}