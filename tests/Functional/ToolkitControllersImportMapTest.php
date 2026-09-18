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
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;

final class ToolkitControllersImportMapTest extends KernelTestCase
{
    public function testToolkitControllersAreAutoRegisteredFromTheVendoredKits()
    {
        self::bootKernel();

        /** @var ImportMapConfigReader $reader */
        $reader = self::getContainer()->get('asset_mapper.importmap.config_reader');
        $entries = $reader->getEntries();

        // Base Markdown controllers are explicit importmap entries.
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/tabs_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/popover_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/clipboard_controller.js'));

        /** @var AssetMapperInterface $assetMapper */
        $assetMapper = self::getContainer()->get('asset_mapper');
        $loader = $assetMapper->getAsset('toolkit-controllers.loader.js');
        self::assertNotNull($loader);

        $implicitImports = [];
        foreach ($loader->getJavaScriptImports() as $import) {
            if ($import->addImplicitlyToImportMap) {
                $implicitImports[] = $import->assetLogicalPath;
            }
        }

        self::assertContains('@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js', $implicitImports);
        self::assertContains('@symfony/ux-toolkit/kits/flowbite-4/modal/assets/controllers/flowbite_modal_controller.js', $implicitImports);

        // The base CSS stays an explicit entry.
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/styles/toolkit.css'));
    }
}
