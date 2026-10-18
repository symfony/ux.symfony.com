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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Twig\Environment;

/**
 * Renders a Toolkit preview as a standalone page, in the context of its kit, served from the page that embeds
 * it (its canonical URL).
 */
final class PreviewRenderer
{
    public function __construct(
        private readonly ToolkitService $toolkitService,
        private readonly Environment $twig,
        #[Autowire(service: 'ux_toolkit.kit.kit_context_runner')]
        private readonly KitContextRunner $kitContextRunner,
        #[Autowire(service: 'profiler')]
        private readonly ?Profiler $profiler = null,
    ) {
    }

    /**
     * The Nth example of a recipe README (from its `@ToolkitPreview` template), or null when there is none.
     *
     * @param int $number 1-based, in reading order
     */
    public function respondWithExample(Request $request, string $kitId, Recipe $recipe, int $number, string $canonicalUrl): ?Response
    {
        $template = \sprintf('@ToolkitPreview/%s/%s', $kitId, $recipe->name);
        $block = 'preview_'.$number;

        if (!$this->twig->getLoader()->exists($template) || !$this->twig->load($template)->hasBlock($block)) {
            return null;
        }

        return $this->respond($request, $kitId, $template, $block, $recipe, $canonicalUrl);
    }

    /**
     * A whole template of the site, rendered with the kit components.
     */
    public function respondWithTemplate(Request $request, string $kitId, string $template, string $canonicalUrl): Response
    {
        return $this->respond($request, $kitId, $template, null, null, $canonicalUrl);
    }

    private function respond(Request $request, string $kitId, string $template, ?string $block, ?Recipe $recipe, string $canonicalUrl): Response
    {
        $this->profiler?->disable();

        $html = $this->kitContextRunner->runForKit(
            $this->toolkitService->getKit($kitId),
            fn () => $this->twig->render('toolkit/preview.html.twig', [
                'template' => $template,
                'block' => $block,
                'entrypoint' => PreviewAssetsGenerator::entrypointName($kitId),
                'full_page' => RecipeType::Block === $recipe?->manifest->type,
            ]),
            $recipe,
        );

        $response = new Response($html, headers: ['Link' => \sprintf('<%s>; rel="canonical"', $canonicalUrl)]);
        $response->setPublic()->setMaxAge(86400)->setEtag(hash('xxh128', $html));
        $response->isNotModified($request);

        return $response;
    }
}
