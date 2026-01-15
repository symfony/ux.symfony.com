<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Toolkit;

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Recipe $component;

    /**
     * @see https://regex101.com/r/8L2pPy/1
     */
    private const RE_CODE_BLOCK = '/```(?P<language>[a-z]+?)\s*(?P<options>\{.+?\})?\n(?P<code>.+?)```/s';

    public function __construct(
        private readonly ToolkitService $toolkitService,
        private readonly \Twig\Environment $twig,
    ) {
    }

    public function getMarkdownContent(): string
    {
        $kit = $this->toolkitService->getKit($this->kitId);
        $pool = $this->toolkitService->resolveRecipePool($kit, $this->component);
        $apiReference = $this->toolkitService->extractRecipeApiReference($this->component);

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

        $templateName = \sprintf('toolkit/docs/%s/%s.md.twig', $this->kitId->value, $this->component->name);

        return $this->twig->render($templateName, [
            'kit_id' => $this->kitId,
            'component' => $this->component,
            'files' => $files,
            'php_package_dependencies' => $pool->getPhpPackageDependencies(),
            'npm_package_dependencies' => $pool->getNpmPackageDependencies(),
            'importmap_package_dependencies' => $pool->getImportmapPackageDependencies(),
            'api_reference' => $apiReference,
        ]);
    }
}
