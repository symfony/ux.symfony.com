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

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Recipe\RecipeType;

class ComponentsController extends AbstractController
{
    public function __construct(
        private ToolkitService $toolkitService,
        private UxPackageRepository $uxPackageRepository,
    ) {
    }

    #[Route('/toolkit/kits/{kitId}/components/{componentName}', name: 'app_toolkit_component')]
    public function showComponent(ToolkitKitId $kitId, string $componentName): Response
    {
        $kit = $this->toolkitService->getKit($kitId);
        if (null === $component = $kit->getRecipe($componentName, type: RecipeType::Component)) {
            throw $this->createNotFoundException(\sprintf('Component "%s" not found', $componentName));
        }

        $package = $this->uxPackageRepository->find('toolkit');

        return $this->render('toolkit/component.html.twig', [
            'package' => $package,
            'components' => $kit->getRecipes(RecipeType::Component),
            'blocks' => $kit->getRecipes(RecipeType::Block),
            'kit' => $kit,
            'kit_id' => $kitId,
            'component' => $component,
        ]);
    }

    #[Route('/toolkit/kits/{kitId}/blocks/{blockName}', name: 'app_toolkit_block')]
    public function showBlock(ToolkitKitId $kitId, string $blockName): Response
    {
        $kit = $this->toolkitService->getKit($kitId);
        if (null === $block = $kit->getRecipe($blockName, type: RecipeType::Block)) {
            throw $this->createNotFoundException(\sprintf('Block "%s" not found', $blockName));
        }

        $package = $this->uxPackageRepository->find('toolkit');

        return $this->render('toolkit/block.html.twig', [
            'package' => $package,
            'components' => $kit->getRecipes(RecipeType::Component),
            'blocks' => $kit->getRecipes(RecipeType::Block),
            'kit' => $kit,
            'kit_id' => $kitId,
            'block_recipe' => $block,
        ]);
    }

    #[Route('/toolkit/component_preview', name: 'app_toolkit_component_preview')]
    public function previewComponent(
        Request $request,
        #[MapQueryParameter] ToolkitKitId $kitId,
        #[MapQueryParameter] string $code,
        #[MapQueryParameter] string $height,
        #[MapQueryParameter] ?string $recipe,
        UriSigner $uriSigner,
        \Twig\Environment $twig,
        #[Autowire(service: 'ux_toolkit.kit.kit_context_runner')]
        KitContextRunner $kitContextRunner,
        #[Autowire(service: 'profiler')]
        ?Profiler $profiler,
    ): Response {
        if (!$uriSigner->checkRequest($request)) {
            throw new BadRequestHttpException('Request is invalid.');
        }

        $profiler?->disable();

        $kit = $this->toolkitService->getKit($kitId);

        $isBlock = null !== $recipe && null !== ($r = $kit->getRecipe($recipe)) && RecipeType::Block === $r->manifest->type;
        $bodyClass = $isBlock
            ? 'w-full justify-center items-center text-neutral-800 dark:text-neutral-300'
            : "flex min-h-[{$height}] w-full justify-center p-5 items-center text-neutral-800 dark:text-neutral-300";

        $template = $twig->createTemplate(<<<HTML
            <html lang="en">
                <head>
                    <meta charset="utf-8">
                    <title>Preview</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <script>
                        const theme = localStorage.getItem('user-theme');
                        if (theme) {
                            document.documentElement.classList.add(theme);
                        }
                        window.addEventListener('storage', (event) => {
                            if (event.key === 'user-theme') {
                                document.documentElement.classList.toggle('dark', event.newValue === 'dark');
                                document.documentElement.classList.toggle('light', event.newValue === 'light');
                            }
                        });
                    </script>
                    {{ importmap('toolkit-{$kitId->value}') }}
                </head>
                <body class="{$bodyClass}">{$code}</body>
            </html>
            HTML);

        $prioritizedRecipe = null !== $recipe ? $kit->getRecipe($recipe) : null;

        return new Response(
            $kitContextRunner->runForKit($kit, static fn () => $twig->render($template), $prioritizedRecipe),
            Response::HTTP_OK,
            ['X-Robots-Tag' => 'noindex, nofollow']
        );
    }
}
