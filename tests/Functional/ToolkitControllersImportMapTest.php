<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;

final class ToolkitControllersImportMapTest extends KernelTestCase
{
    public function testToolkitControllersAreAutoRegisteredFromTheVendoredKits(): void
    {
        self::bootKernel();

        /** @var ImportMapConfigReader $reader */
        $reader = self::getContainer()->get('asset_mapper.importmap.config_reader');
        $entries = $reader->getEntries();

        // Base Markdown controllers, derived (no longer hand-listed).
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/tabs_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/popover_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/clipboard_controller.js'));

        // Per-kit component controllers, derived.
        self::assertTrue($entries->has('@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/kits/flowbite-4/modal/assets/controllers/modal_controller.js'));

        // The base CSS stays an explicit entry.
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/styles/toolkit.css'));
    }
}
