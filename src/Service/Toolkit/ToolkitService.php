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
use App\Service\CommonMark\ConverterFactory;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Installer\Pool;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\RegistryFactory;
use Twig\Environment;

use function Symfony\Component\String\s;

class ToolkitService
{
    /** @var array<value-of<ToolkitKitId>, Kit> */
    private array $kits = [];

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
        private Environment $twig,
        private ConverterFactory $converterFactory,
    ) {
    }

    public function getKit(ToolkitKitId $kitId): Kit
    {
        return $this->kits[$kitId->value] ??= $this->registryFactory->getForKit($kitId->value)->getKit($kitId->value);
    }

    /**
     * @return array<ToolkitKitId,Kit>
     */
    public function getKits(): array
    {
        $kits = [];
        foreach (ToolkitKitId::cases() as $kitId) {
            $kits[$kitId->value] = $this->getKit($kitId);
        }

        return $kits;
    }

    public function resolveRecipePool(Kit $kit, Recipe $component): Pool
    {
        return (new PoolResolver())->resolveForRecipe($kit, $component);
    }

    /**
     * @return list<array{level: int, title: string, id: string}>
     */
    public function getRecipeTocItems(ToolkitKitId $kitId, Recipe $recipe): array
    {
        $environment = ($this->converterFactory)()->getEnvironment();
        $document = new MarkdownParser($environment)->parse($this->renderRecipeMarkdown($kitId, $recipe));
        $renderer = new HtmlRenderer($environment);

        $items = [];
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (!$node instanceof Heading) {
                continue;
            }
            $level = $node->getLevel();
            if ($level < 2 || $level > 3) {
                continue;
            }
            $id = $node->data->get('attributes/id', null);
            if (null === $id) {
                continue;
            }
            $items[] = [
                'level' => $level,
                'title' => (string) $renderer->renderNodes($node->children()),
                'id' => $id,
            ];
        }

        return $items;
    }

    public function renderRecipeMarkdown(ToolkitKitId $kitId, Recipe $recipe, bool $isLlm = false): string
    {
        $kit = $this->getKit($kitId);
        $pool = $this->resolveRecipePool($kit, $recipe);
        $apiReference = $this->extractRecipeApiReference($recipe);

        $files = [];
        foreach ($pool->getFiles() as $recipeFullPath => $recipeFiles) {
            foreach ($recipeFiles as $recipeFile) {
                $recipeFileSourcePath = Path::join($recipeFullPath, $recipeFile->sourceRelativePathName);
                $files[] = [
                    'path_name' => $recipeFile->sourceRelativePathName,
                    'content' => file_get_contents($recipeFileSourcePath),
                    'language' => pathinfo($recipeFileSourcePath, \PATHINFO_EXTENSION),
                ];
            }
        }

        return $this->twig->render(\sprintf('toolkit/docs/%s/%s.md.twig', $kitId->value, $recipe->name), [
            'kit_id' => $kitId,
            'kit' => $kit,
            'component' => $recipe,
            'files' => $files,
            'php_package_dependencies' => $pool->getPhpPackageDependencies(),
            'npm_package_dependencies' => $pool->getNpmPackageDependencies(),
            'importmap_package_dependencies' => $pool->getImportmapPackageDependencies(),
            'api_reference' => $apiReference,
            'is_llm' => $isLlm,
        ]);
    }

    /**
     * @return array<string, array{props: list<array{name: string, type: string, description: string}>, blocks: list<array{name: string, description: string}>}>
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
