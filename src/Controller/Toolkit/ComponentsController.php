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

use App\Service\Toolkit\PreviewRenderer;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\Toolkit\Registry\LocalRegistry;

class ComponentsController extends AbstractController
{
    public function __construct(
        private ToolkitService $toolkitService,
        private UxPackageRepository $uxPackageRepository,
    ) {
    }

    #[Route('/toolkit/kits/{kitId}/components', name: 'app_toolkit_kit_components')]
    public function index(string $kitId): Response
    {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);

        return $this->render('toolkit/components.html.twig', [
            'package' => $this->uxPackageRepository->find('toolkit'),
            'kit' => $kit,
            'kit_id' => $kitId,
            'components' => $kit->getRecipes(RecipeType::Component),
        ]);
    }

    #[Route('/toolkit/kits/{kitId}/components/{componentName}', name: 'app_toolkit_component')]
    public function showComponent(
        Request $request,
        string $kitId,
        string $componentName,
        PreviewRenderer $previewRenderer,
        #[MapQueryParameter] ?int $preview = null,
    ): Response {
        if (!LocalRegistry::exists($kitId)) {
            throw $this->createNotFoundException(\sprintf('Kit "%s" not found', $kitId));
        }

        $kit = $this->toolkitService->getKit($kitId);
        if (null === $component = $kit->getRecipe($componentName, type: RecipeType::Component)) {
            throw $this->createNotFoundException(\sprintf('Component "%s" not found', $componentName));
        }

        if (null !== $preview) {
            $canonicalUrl = $this->generateUrl('app_toolkit_component', ['kitId' => $kitId, 'componentName' => $componentName], UrlGeneratorInterface::ABSOLUTE_URL);

            return $previewRenderer->respondWithExample($request, $kitId, $component, $preview, $canonicalUrl)
                ?? throw $this->createNotFoundException(\sprintf('Preview %d of "%s" not found', $preview, $componentName));
        }

        return $this->render('toolkit/component.html.twig', [
            'package' => $this->uxPackageRepository->find('toolkit'),
            'kit' => $kit,
            'kit_id' => $kitId,
            'component' => $component,
            'toc_items' => $this->toolkitService->getRecipeTocItems($kitId, $component),
        ]);
    }

    /**
     * Previews used to be served here from a signed URL carrying their code.
     */
    #[Route('/toolkit/component_preview', name: 'app_toolkit_component_preview_gone')]
    public function previewGone(): Response
    {
        return new Response(status: Response::HTTP_GONE);
    }
}
