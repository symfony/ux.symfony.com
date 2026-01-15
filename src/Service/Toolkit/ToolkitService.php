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

use App\Enum\ToolkitKitId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Installer\Pool;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\RegistryFactory;

use function Symfony\Component\String\s;

class ToolkitService
{
    /**
     * @see https://regex101.com/r/3JXNX7/1
     */
    private const RE_API_PROPS = '/{#\s+@prop\s+(?P<name>\w+)\s+(?P<type>[^\s]+)\s+(?P<description>.+?)\s+#}/s';

    /**
     * @see https://regex101.com/r/jYjXpq/1
     */
    private const RE_API_BLOCKS = '/{#\s+@block\s+(?P<name>\w+)\s+(?P<description>.+?)\s+#}/s';

    public function __construct(
        #[Autowire(service: 'ux_toolkit.registry.registry_factory')]
        private RegistryFactory $registryFactory,
    ) {
    }

    public function getKit(ToolkitKitId $kit): Kit
    {
        return $this->getKits()[$kit->value] ?? throw new \InvalidArgumentException(\sprintf('Kit "%s" not found', $kit->value));
    }

    /**
     * @return array<ToolkitKitId,Kit>
     */
    public function getKits(): array
    {
        static $kits = null;

        if (null === $kits) {
            $kits = [];
            foreach (ToolkitKitId::cases() as $kit) {
                $kits[$kit->value] = $this->registryFactory->getForKit($kit->value)->getKit($kit->value);
            }
        }

        return $kits;
    }

    public function resolveRecipePool(Kit $kit, Recipe $component): Pool
    {
        return (new PoolResolver())->resolveForRecipe($kit, $component);
    }

    /**
     * @return array<string, array{
     *     props: array<array{name: string, type: string, description: string}>,
     *     blocks: array<array{name: string, description: string}>
     * }
     */
    public function extractRecipeApiReference(Recipe $recipe): array
    {
        $apiReference = [];

        foreach ($recipe->getFiles() as $file) {
            $filePath = Path::join($recipe->absolutePath, $file->sourceRelativePathName);
            if (!file_exists($filePath)) {
                continue;
            }

            $fileContent = s(file_get_contents($filePath));

            // Twig files...
            if (str_ends_with($file->sourceRelativePathName, '.html.twig')) {
                $props = $fileContent->match(self::RE_API_PROPS, \PREG_SET_ORDER);
                $blocks = $fileContent->match(self::RE_API_BLOCKS, \PREG_SET_ORDER);

                if ([] === $props && [] === $blocks) {
                    continue;
                }

                $componentName = s($file->sourceRelativePathName)
                    ->replace('templates/components/', '')
                    ->replace('.html.twig', '')
                    ->replace('/', ':')
                    ->toString();

                $apiReference[$componentName] = [
                    'props' => array_map(static fn (array $prop) => [
                        'name' => $prop['name'],
                        'type' => $prop['type'],
                        'description' => trim(preg_replace('/\s+/', ' ', $prop['description'])),
                    ], $props),
                    'blocks' => array_map(static fn (array $block) => [
                        'name' => $block['name'],
                        'description' => trim(preg_replace('/\s+/', ' ', $block['description'])),
                    ], $blocks),
                ];
            }
        }

        return $apiReference;
    }
}
