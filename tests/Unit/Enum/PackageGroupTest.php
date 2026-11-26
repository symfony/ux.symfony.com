<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Enum;

use App\Enum\PackageGroup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageGroup::class)]
final class PackageGroupTest extends TestCase
{
    public function testEveryGroupAsksItsOwnQuestion(): void
    {
        $groups = PackageGroup::cases();

        $questions = array_map(static fn (PackageGroup $group): string => $group->question(), $groups);

        self::assertSame($questions, array_unique($questions));
        foreach ($questions as $question) {
            self::assertStringEndsWith('?', $question);
        }
    }
}
