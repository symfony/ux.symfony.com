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
use App\Service\Toolkit\PreviewRenderer;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

class BlocksController extends AbstractController
{
    public function __construct(
        private ToolkitService $toolkitService,
        private UxPackageRepository $uxPackageRepository,
        private BlockSectionResolver $blockSectionResolver,
    ) {
    }

    #[Route('/toolkit/kits/{kitId}/blocks', name: 'app_toolkit_kit_blocks')]
    public function index(string $kitId): Response
    {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);

        return $this->render('toolkit/blocks.html.twig', [
            'package' => $this->uxPackageRepository->find('toolkit'),
            'kit' => $kit,
            'kit_id' => $kitId,
            'sections' => $this->blockSectionResolver->forKit($kit),
        ]);
    }

    #[Route('/toolkit/kits/{kitId}/blocks/{section}', name: 'app_toolkit_block_section')]
    public function showSection(
        Request $request,
        string $kitId,
        string $section,
        PreviewRenderer $previewRenderer,
        #[MapQueryParameter] ?string $preview = null,
    ): Response {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);
        if (null === $blockSection = $this->blockSectionResolver->findSection($kit, $section)) {
            throw $this->createNotFoundException(\sprintf('Block section "%s" not found', $section));
        }

        if (null !== $preview) {
            $recipe = array_find($blockSection->recipes, static fn (Recipe $recipe) => $recipe->name === $preview);
            $canonicalUrl = $this->generateUrl('app_toolkit_block_section', ['kitId' => $kitId, 'section' => $section], UrlGeneratorInterface::ABSOLUTE_URL);

            return (null !== $recipe ? $previewRenderer->respondWithExample($request, $kitId, $recipe, 1, $canonicalUrl) : null)
                ?? throw $this->createNotFoundException(\sprintf('Block "%s" not found in section "%s"', $preview, $section));
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
     * @return list<array{recipe: Recipe, code: ?string, language: ?string, preview_url: ?string}>
     */
    private function variantsOf(string $kitId, BlockSection $section): array
    {
        return array_map(function (Recipe $recipe) use ($kitId, $section): array {
            if (null === $example = $recipe->getExamples()[0] ?? null) {
                return ['recipe' => $recipe, 'code' => null, 'language' => null, 'preview_url' => null];
            }

            return [
                'recipe' => $recipe,
                'code' => $example['code'],
                'language' => $example['language'],
                'preview_url' => $this->generateUrl('app_toolkit_block_section', ['kitId' => $kitId, 'section' => $section->slug, 'preview' => $recipe->name], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }, $section->recipes);
    }
}
