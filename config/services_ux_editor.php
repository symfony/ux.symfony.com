<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Register UX Editor bridge services explicitly because bridges ship without
// their own Symfony bundle class (they are not auto-loaded by Flex recipes).

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\UX\Editor\Bridge\CKEditor\CKEditorBridge;
use Symfony\UX\Editor\Bridge\CKEditor\Preset\WysiwygFullPreset;
use Symfony\UX\Editor\Bridge\CKEditor\Preset\WysiwygMinimalPreset;
use Symfony\UX\Editor\Bridge\CKEditor\Transformer\CKEditorTransformer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\HeaderRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ImageRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ListRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ParagraphRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\QuoteRenderer;
use Symfony\UX\Editor\Bridge\EditorJS\EditorJSBridge;
use Symfony\UX\Editor\Bridge\EditorJS\Preset\BlogStandardPreset;
use Symfony\UX\Editor\Bridge\EditorJS\Transformer\EditorJSTransformer;
use Symfony\UX\Editor\Bridge\GrapesJS\GrapesJSBridge;
use Symfony\UX\Editor\Bridge\GrapesJS\Preset\PageBuilderLandingPreset;
use Symfony\UX\Editor\Bridge\GrapesJS\Transformer\GrapesJSTransformer;

return static function (ContainerConfigurator $c): void {
    $s = $c->services()->defaults()->autowire()->autoconfigure();

    // CKEditor bridge
    $s->set(CKEditorBridge::class)->tag('ux.editor.bridge');
    $s->set(WysiwygMinimalPreset::class)->tag('ux.editor.preset', ['name' => 'wysiwyg.minimal']);
    $s->set(WysiwygFullPreset::class)->tag('ux.editor.preset', ['name' => 'wysiwyg.full']);
    $s->set(CKEditorTransformer::class);

    // EditorJS bridge
    $s->set(EditorJSBridge::class)->tag('ux.editor.bridge');
    $s->set(BlogStandardPreset::class)->tag('ux.editor.preset', ['name' => 'blog.standard']);
    $s->set(EditorJSTransformer::class);
    $s->set(HeaderRenderer::class);
    $s->set(ParagraphRenderer::class);
    $s->set(ListRenderer::class);
    $s->set(ImageRenderer::class);
    $s->set(QuoteRenderer::class);

    // GrapesJS bridge
    $s->set(GrapesJSBridge::class)->tag('ux.editor.bridge');
    $s->set(PageBuilderLandingPreset::class)->tag('ux.editor.preset', ['name' => 'page_builder.landing']);
    $s->set(GrapesJSTransformer::class);
};
