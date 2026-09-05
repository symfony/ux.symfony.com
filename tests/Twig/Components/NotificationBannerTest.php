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

use App\Twig\Components\NotificationBanner;
use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\LiveResponder;

final class NotificationBannerTest extends TestCase
{
    public function testPlainRemoval()
    {
        self::assertTrue((new NotificationBanner())->dismiss()->isRemove());
    }

    public function testRemovalWithEvents()
    {
        $component = new NotificationBanner();
        $responder = new LiveResponder();
        $component->setLiveResponder($responder);

        self::assertTrue($component->dismissWithEvents()->isRemove());
        self::assertSame([
            ['event' => 'dismissed', 'payload' => ['id' => 42]],
        ], $responder->getBrowserEventsToDispatch());
    }
}
