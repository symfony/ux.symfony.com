<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service\Icon;

use App\Service\Icon\IconCollection;
use PHPUnit\Framework\TestCase;

class IconCollectionTest extends TestCase
{
    public function testItRendersAnIconWithTheCollectionSize()
    {
        $collection = new IconCollection([
            'width' => 24,
            'height' => 24,
            'icons' => ['house' => ['body' => '<path d="M0 0"/>']],
        ]);

        $this->assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0"/></svg>',
            $collection->svg('house'),
        );
    }

    public function testItFallsBackTo16WhenTheCollectionHasNoSize()
    {
        $collection = new IconCollection(['icons' => ['house' => ['body' => '<path/>']]]);

        $this->assertStringContainsString('width="16" height="16" viewBox="0 0 16 16"', $collection->svg('house'));
    }

    public function testAnIconOverridesTheCollectionSizeAndOrigin()
    {
        $collection = new IconCollection([
            'width' => 24,
            'height' => 24,
            'icons' => ['house' => ['body' => '<path/>', 'width' => 20, 'height' => 32, 'left' => -2, 'top' => 4]],
        ]);

        $this->assertStringContainsString('width="20" height="32" viewBox="-2 4 20 32"', $collection->svg('house'));
    }

    public function testItResolvesAnAlias()
    {
        $collection = new IconCollection([
            'width' => 24,
            'height' => 24,
            'icons' => ['house' => ['body' => '<path d="M0 0"/>']],
            'aliases' => ['home' => ['parent' => 'house']],
        ]);

        $this->assertSame($collection->svg('house'), $collection->svg('home'));
    }

    public function testItResolvesAChainOfAliases()
    {
        $collection = new IconCollection([
            'icons' => ['house' => ['body' => '<path/>']],
            'aliases' => [
                'home' => ['parent' => 'house'],
                'dwelling' => ['parent' => 'home'],
            ],
        ]);

        $this->assertStringContainsString('<path/>', $collection->svg('dwelling'));
    }

    public function testAnAliasOverridesTheSizeOfItsParent()
    {
        $collection = new IconCollection([
            'width' => 24,
            'height' => 24,
            'icons' => ['house' => ['body' => '<path/>']],
            'aliases' => ['home' => ['parent' => 'house', 'width' => 48]],
        ]);

        $this->assertStringContainsString('width="48" height="24" viewBox="0 0 48 24"', $collection->svg('home'));
    }

    public function testTheClosestAliasWinsOverAnOuterOne()
    {
        $collection = new IconCollection([
            'icons' => ['house' => ['body' => '<path/>']],
            'aliases' => [
                'home' => ['parent' => 'house', 'width' => 48],
                'dwelling' => ['parent' => 'home', 'width' => 64],
            ],
        ]);

        $this->assertStringContainsString('width="64"', $collection->svg('dwelling'));
    }

    public function testItReturnsNullForAnUnknownIcon()
    {
        $collection = new IconCollection(['icons' => ['house' => ['body' => '<path/>']]]);

        $this->assertNull($collection->svg('nope'));
    }

    public function testItReturnsNullForAnEmptyCollection()
    {
        $this->assertNull((new IconCollection([]))->svg('house'));
    }

    public function testItReturnsNullForAnAliasPointingAtNothing()
    {
        $collection = new IconCollection([
            'icons' => [],
            'aliases' => ['home' => ['parent' => 'house']],
        ]);

        $this->assertNull($collection->svg('home'));
    }

    public function testItDoesNotLoopOnACycleOfAliases()
    {
        $collection = new IconCollection([
            'icons' => [],
            'aliases' => [
                'home' => ['parent' => 'house'],
                'house' => ['parent' => 'home'],
            ],
        ]);

        $this->assertNull($collection->svg('home'));
    }
}
