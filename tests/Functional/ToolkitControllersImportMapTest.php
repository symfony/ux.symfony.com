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
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;

final class ToolkitControllersImportMapTest extends KernelTestCase
{
    public function testToolkitControllersAreRegisteredByTheKitPreviewEntrypoints(): void
    {
        self::bootKernel();

        /** @var ImportMapConfigReader $reader */
        $reader = self::getContainer()->get('asset_mapper.importmap.config_reader');
        $entries = $reader->getEntries();

        // Base Markdown controllers are explicit importmap entries.
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/tabs_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/popover_controller.js'));
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/controllers/clipboard_controller.js'));

        self::assertContains('@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js', $this->getKitPreviewImports($reader, 'shadcn'));
        self::assertContains('@symfony/ux-toolkit/kits/flowbite-4/modal/assets/controllers/flowbite_modal_controller.js', $this->getKitPreviewImports($reader, 'flowbite-4'));

        // The base CSS stays an explicit entry.
        self::assertTrue($entries->has('@symfony/ux-toolkit/assets/styles/toolkit.css'));
    }

    /**
     * @return list<string>
     */
    private function getKitPreviewImports(ImportMapConfigReader $reader, string $kitId): array
    {
        /** @var AssetMapperInterface $assetMapper */
        $assetMapper = self::getContainer()->get('asset_mapper');
        $entrypoint = $reader->getEntries()->get(PreviewAssetsGenerator::entrypointName($kitId));
        $asset = $assetMapper->getAssetFromSourcePath($entrypoint->path);

        return array_map(static fn ($import) => $import->assetLogicalPath, $asset->getJavaScriptImports());
    }
}
