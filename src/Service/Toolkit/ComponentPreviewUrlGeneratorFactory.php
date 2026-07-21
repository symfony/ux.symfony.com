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
 * Builds a kit-scoped {@see PreviewUrlGenerator} for the Toolkit's RecipeDocRenderer: the generated
 * URL points at the app_toolkit_component_preview controller, signed so the preview iframe can render
 * arbitrary example code safely.
 */
final class ComponentPreviewUrlGeneratorFactory
{
    public function __construct(
        private readonly UriSigner $uriSigner,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function forKit(string $kitId): PreviewUrlGenerator
    {
        return new class($kitId, $this->uriSigner, $this->urlGenerator) implements PreviewUrlGenerator {
            public function __construct(
                private readonly string $kitId,
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
                        'height' => $options->height,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ));
            }
        };
    }
}
