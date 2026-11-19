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

use App\Model\Demo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Demo::class)]
final class DemoTest extends TestCase
{
    public function testRouteAndTemplateFollowThePackageAndIdentifier(): void
    {
        $demo = new Demo('live-component', 'infinite-scroll-2', 'Infinite Scroll', 'Lorem ipsum.', 'smnandre', '2026-01-01', [], 'Lorem ipsum dolor sit amet.');

        self::assertSame('app_demo_live_component_infinite_scroll_2', $demo->getRoute());
        self::assertSame('demos/live_component/infinite_scroll_2.html.twig', $demo->getTemplate());
    }

    public function testScreenshotsAreStoredByPackage(): void
    {
        $demo = new Demo('turbo', 'infinite-scroll', 'Infinite Scroll', 'Lorem ipsum.', 'ker0x', '2026-01-01', [], 'Lorem ipsum dolor sit amet.');

        self::assertSame('images/demos/turbo/infinite-scroll.png', $demo->getScreenshotFilename());
        self::assertSame('images/demos/turbo/infinite-scroll-1280x720.png', $demo->getScreenshotFilename('1280x720'));
    }

    public function testRouteAndTemplateCanBeOverridden(): void
    {
        $demo = new Demo(
            'pagination',
            'cursor-pagination',
            'Cursor Pagination',
            'Lorem ipsum.',
            'smnandre',
            '2026-01-01',
            [],
            'Lorem ipsum dolor sit amet.',
            route: 'app_demo_pagination_cursor',
            template: 'demos/pagination/cursor.html.twig',
        );

        self::assertSame('app_demo_pagination_cursor', $demo->getRoute());
        self::assertSame('demos/pagination/cursor.html.twig', $demo->getTemplate());
    }
}
