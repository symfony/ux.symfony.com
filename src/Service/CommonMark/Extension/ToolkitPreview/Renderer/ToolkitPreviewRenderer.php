<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\ToolkitPreview\Renderer;

use App\Service\CommonMark\Extension\ToolkitPreview\Node\ToolkitPreview;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ToolkitPreviewRenderer implements NodeRendererInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
        private \Twig\Environment $twig,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof ToolkitPreview) {
            throw new \InvalidArgumentException(\sprintf('Expected instance of "%s", got "%s"', ToolkitPreview::class, $node::class));
        }

        $options = $node->getOptions();
        $height = $options['height'] ?? '200px';

        $params = ['kitId' => $node->getKitId()->value, 'code' => $node->getLiteral(), 'height' => $height];
        if (null !== $node->getRecipeName()) {
            $params['recipe'] = $node->getRecipeName();
        }

        $previewUrl = $this->uriSigner->sign(
            $this->urlGenerator->generate(
                'app_toolkit_component_preview',
                $params,
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );

        return $this->twig->render('toolkit/docs/_preview.html.twig', [
            'previewUrl' => $previewUrl,
            'height' => $height,
        ]);
    }
}
