<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit;

use App\Service\Toolkit\ToolkitService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ToolkitRenderSmokeTest extends KernelTestCase
{
    public function testRendersRecipeReadmeToHtmlWithLivePreview()
    {
        $svc = self::getContainer()->get(ToolkitService::class);
        $kit = $svc->getKit('shadcn');
        $recipe = $kit->getRecipe('badge');
        self::assertNotNull($recipe);

        $html = $svc->renderRecipeHtml('shadcn', $recipe);

        self::assertStringContainsString('Badge', $html);        // title from README.md
        self::assertStringContainsString('<iframe', $html);      // live preview via PreviewUrlGenerator
        self::assertStringContainsString('toolkit/component_preview', $html); // signed preview URL path
        self::assertMatchesRegularExpression('/_hash=|_signature=/', $html);  // UriSigner signature
        self::assertStringContainsString('Installation', $html);
    }

    public function testTocItemsMatchHeadingIdsInRenderedHtml()
    {
        $svc = self::getContainer()->get(ToolkitService::class);
        $kit = $svc->getKit('shadcn');
        $recipe = $kit->getRecipe('badge');
        self::assertNotNull($recipe);

        $html = $svc->renderRecipeHtml('shadcn', $recipe);
        $tocItems = $svc->getRecipeTocItems('shadcn', $recipe);

        self::assertNotEmpty($tocItems, 'The recipe should expose at least one TOC item.');

        foreach ($tocItems as $item) {
            self::assertArrayHasKey('id', $item);
            self::assertNotSame('', $item['id'], 'Each TOC item must have a non-empty id.');
            // The heading in the rendered HTML must carry the matching id attribute.
            self::assertStringContainsString('id="'.$item['id'].'"', $html);
        }
    }
}
