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

use App\Twig\Components\RemovalExample;
use PHPUnit\Framework\TestCase;

final class RemovalExampleTest extends TestCase
{
    public function testResetChangesOnlyItsOwnRevision()
    {
        $component = new RemovalExample();
        $otherComponent = new RemovalExample();

        $component->reset();

        self::assertSame(1, $component->revision);
        self::assertSame(0, $otherComponent->revision);
    }
}
