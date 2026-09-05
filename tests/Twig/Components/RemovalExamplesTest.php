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

use App\Twig\Components\RemovalExamples;
use PHPUnit\Framework\TestCase;

final class RemovalExamplesTest extends TestCase
{
    public function testDefinesThreeRemovalExamplesWithTwoActions()
    {
        $component = new RemovalExamples();
        $examples = $component->getExamples();

        self::assertSame(['plain', 'events', 'animated'], array_keys($examples));
        self::assertSame('dismiss', $examples['plain']['action']);
        self::assertSame('dismissWithEvents', $examples['events']['action']);
        self::assertSame('dismiss', $examples['animated']['action']);
        self::assertSame('lucide:x', $examples['plain']['icon']);
        self::assertSame('Browser event', $examples['events']['resultLabel']);
        self::assertSame('dismissed { id: 42 }', $examples['events']['resultDetail']);
        self::assertSame('Animation complete', $examples['animated']['resultLabel']);
    }
}
