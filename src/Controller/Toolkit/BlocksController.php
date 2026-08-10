<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Toolkit;

use App\Service\Toolkit\BlockSection;
use App\Service\Toolkit\BlockSectionResolver;
use App\Service\Toolkit\ComponentPreviewUrlGeneratorFactory;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

class BlocksController extends AbstractController
{
    public function __construct(
        private ToolkitService $toolkitService,
        private UxPackageRepository $uxPackageRepository,
        private BlockSectionResolver $blockSectionResolver,
        private ComponentPreviewUrlGeneratorFactory $previewUrlGeneratorFactory,
    ) {
    }

    #[Route('/toolkit/kits/{kitId}/blocks', name: 'app_toolkit_kit_blocks')]
    public function index(string $kitId): Response
    {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);
        $sections = array_map(
            fn (BlockSection $section): array => [
                'section' => $section,
                'variants' => $this->variantsOf($kitId, $section),
            ],
            $this->blockSectionResolver->forKit($kit),
        );

        return $this->render('toolkit/blocks.html.twig', [
            'package' => $this->uxPackageRepository->find('toolkit'),
            'kit' => $kit,
            'kit_id' => $kitId,
            'sections' => $sections,
        ]);
    }

    #[Route('/toolkit/kits/{kitId}/blocks/{section}', name: 'app_toolkit_block_section')]
    public function showSection(string $kitId, string $section): Response
    {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);
        if (null === $blockSection = $this->blockSectionResolver->findSection($kit, $section)) {
            throw $this->createNotFoundException(\sprintf('Block section "%s" not found', $section));
        }

        return $this->render('toolkit/block_section.html.twig', [
            'package' => $this->uxPackageRepository->find('toolkit'),
            'kit' => $kit,
            'kit_id' => $kitId,
            'section' => $blockSection,
            'variants' => $this->variantsOf($kitId, $blockSection),
        ]);
    }

    /**
     * @return list<array{recipe: Recipe, preview_url: ?string}>
     */
    private function variantsOf(string $kitId, BlockSection $section): array
    {
        return array_map(
            fn (Recipe $recipe): array => [
                'recipe' => $recipe,
                'preview_url' => $this->previewUrl($kitId, $recipe),
            ],
            $section->recipes,
        );
    }

    private function previewUrl(string $kitId, Recipe $recipe): ?string
    {
        $examples = $recipe->getExamples();
        if ([] === $examples) {
            return null;
        }

        return $this->previewUrlGeneratorFactory
            ->forRecipe($kitId, $recipe->name)
            ->generate($examples[0]['code'], $examples[0]['options']);
    }
}
