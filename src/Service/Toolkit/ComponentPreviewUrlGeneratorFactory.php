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

use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;

/**
 * Builds a recipe-scoped {@see PreviewUrlGenerator} for the Toolkit's RecipeDocRenderer: the generated
 * URL points at the app_toolkit_component_preview controller, signed so the preview iframe can render
 * arbitrary example code safely. The recipe is forwarded so the controller can prioritize its templates
 * when several recipes ship a component of the same name (e.g. login-01 and login-02 both define a LoginForm).
 */
final class ComponentPreviewUrlGeneratorFactory
{
    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function forRecipe(string $kitId, string $recipeName): PreviewUrlGenerator
    {
        return new class($kitId, $recipeName, $this->uriSigner, $this->urlGenerator) implements PreviewUrlGenerator {
            public function __construct(
                private readonly string $kitId,
                private readonly string $recipeName,
                private readonly UriSigner $uriSigner,
                private readonly UrlGeneratorInterface $urlGenerator,
            ) {
            }

            public function generate(string $code, CodeOptions $options): string
            {
                return $this->uriSigner->sign($this->urlGenerator->generate(
                    'app_toolkit_component_preview',
                    [
                        'kitId' => $this->kitId,
                        'code' => $code,
                        'recipe' => $this->recipeName,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ));
            }
        };
    }
}
