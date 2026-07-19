<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Toolkit;

use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntries;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Registers the UX Toolkit's Stimulus controllers (base + per-kit), discovered from the vendored
 * kits, as importmap entries so importmap.php does not hand-list one entry per controller.
 *
 * Derived entries are added on read and stripped on write, so `importmap:*` commands never persist
 * them back into importmap.php.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
#[AsDecorator('asset_mapper.importmap.config_reader')]
final class ToolkitControllersImportMapConfigReader extends ImportMapConfigReader
{
    private const PACKAGE = '@symfony/ux-toolkit';

    /** @var array<string, string>|null */
    private ?array $controllers = null;

    public function __construct(
        string $importMapConfigPath,
        #[Autowire(service: 'asset_mapper.importmap.remote_package_storage')]
        RemotePackageStorage $remotePackageStorage,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct($importMapConfigPath, $remotePackageStorage);
    }

    public function getEntries(): ImportMapEntries
    {
        $entries = new ImportMapEntries();
        foreach (parent::getEntries() as $entry) {
            $entries->add($entry);
        }

        foreach ($this->discoverControllers() as $importName => $path) {
            if (!$entries->has($importName)) {
                $entries->add(ImportMapEntry::createLocal($importName, ImportMapType::JS, $path, false));
            }
        }

        return $entries;
    }

    public function writeEntries(ImportMapEntries $entries): void
    {
        $derived = $this->discoverControllers();

        $persisted = new ImportMapEntries();
        foreach ($entries as $entry) {
            if (!isset($derived[$entry->importName])) {
                $persisted->add($entry);
            }
        }

        parent::writeEntries($persisted);
    }

    /**
     * @return array<string, string> importName => importmap path, relative to the project dir
     */
    private function discoverControllers(): array
    {
        if (null !== $this->controllers) {
            return $this->controllers;
        }

        $packageDir = $this->projectDir.'/vendor/symfony/ux-toolkit';

        $controllers = [];
        foreach ([
            $packageDir.'/assets/controllers/*_controller.js',
            $packageDir.'/kits/*/*/assets/controllers/*_controller.js',
        ] as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                $importName = self::PACKAGE.'/'.substr($file, \strlen($packageDir) + 1);
                $controllers[$importName] = './'.substr($file, \strlen($this->projectDir) + 1);
            }
        }

        return $this->controllers = $controllers;
    }
}
