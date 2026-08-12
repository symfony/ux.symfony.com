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

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

/**
 * Compiles assets/toolkit-controllers.loader.js into a per-kit map of lazy controller imports,
 * discovered from the vendored UX Toolkit kits.
 *
 * AssetMapper follows the generated `() => import(...)`, so each controller lands in the importmap
 * (lazy, not preloaded) and the dependency graph stays intact - no controller is hand-listed.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
#[AsTaggedItem(priority: 100)]
final class ToolkitControllersLoaderCompiler implements AssetCompilerInterface
{
    private const SUFFIX = '_controller.js';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath($this->projectDir.'/assets/toolkit-controllers.loader.js');
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        $kitsDir = realpath($this->projectDir.'/vendor/symfony/ux-toolkit/kits');
        if (false === $kitsDir) {
            return "export default {};\n";
        }

        $asset->addFileDependency($kitsDir);

        $kits = [];
        foreach (glob($kitsDir.'/*/*/assets/controllers/*'.self::SUFFIX) ?: [] as $file) {
            $relative = substr($file, \strlen($kitsDir) + 1);
            $kitId = substr($relative, 0, strpos($relative, '/'));
            $identifier = str_replace('_', '-', substr(basename($file), 0, -\strlen(self::SUFFIX)));
            $kits[$kitId][$identifier] = Path::makeRelative($file, \dirname($asset->sourcePath));
        }
        ksort($kits);

        $lines = [];
        foreach ($kits as $kitId => $controllers) {
            ksort($controllers);
            $entries = [];
            foreach ($controllers as $identifier => $importPath) {
                $entries[] = \sprintf('%s: () => import(%s)', json_encode($identifier), json_encode($importPath, \JSON_UNESCAPED_SLASHES));
            }
            $lines[] = \sprintf('  %s: {%s},', json_encode($kitId), implode(', ', $entries));
        }

        return \sprintf("export default {\n%s\n};\n", implode("\n", $lines));
    }
}
