<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark;

use App\Service\CommonMark\Extension\Alert\AlertExtension;
use App\Service\CommonMark\Extension\FencedCode\FencedCodeRenderer;
use App\Service\CommonMark\Extension\Tabs\TabsExtension;
use App\Service\CommonMark\Extension\ToolkitPreview\ToolkitPreviewExtension;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsDecorator('twig.markdown.league_common_mark_converter_factory')]
final class ConverterFactory
{
    public function __construct(
        private ComponentRendererInterface $componentRenderer,
        private UriSigner $uriSigner,
        private UrlGeneratorInterface $urlGenerator,
        private \Twig\Environment $twig,
    ) {
    }

    public function __invoke(bool $withTableOfContents = false): CommonMarkConverter
    {
        $config = [
            'default_attributes' => [
                Table::class => [
                    'class' => 'Wysiwyg_Table',
                ],
            ],
            'mentions' => [
                'github_handle' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => 'https://github.com/%s',
                ],
                'github_issue' => [
                    'prefix' => '#',
                    'pattern' => '\d+',
                    'generator' => 'https://github.com/symfony/ux/issues/%d',
                ],
            ],
            'external_link' => [
                'internal_hosts' => ['/(^|\.)symfony\.com$/'],
            ],
            'heading_permalink' => [
                'apply_id_to_heading' => true,
                // Headings only need their `id` for anchors when rendered as content.
                // The table of contents needs the permalink nodes in the AST to be generated.
                'insert' => $withTableOfContents ? 'before' : 'none',
            ],
        ];

        if ($withTableOfContents) {
            $config['table_of_contents'] = [
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'normalize' => 'flat',
                'position' => 'top',
            ];
        }

        $converter = new CommonMarkConverter($config);

        $environment = $converter->getEnvironment()
            ->addExtension(new DefaultAttributesExtension())
            ->addExtension(new ExternalLinkExtension())
            ->addExtension(new MentionExtension())
            ->addExtension(new FrontMatterExtension())
            ->addExtension(new TableExtension())
            ->addExtension(new HeadingPermalinkExtension())
            ->addExtension(new TabsExtension($this->twig))
            ->addExtension(new AlertExtension($this->twig))
            ->addExtension(new ToolkitPreviewExtension($this->uriSigner, $this->urlGenerator, $this->twig))
            ->addRenderer(FencedCode::class, new FencedCodeRenderer($this->componentRenderer))
        ;

        if ($withTableOfContents) {
            $environment->addExtension(new TableOfContentsExtension());
        }

        return $converter;
    }
}
