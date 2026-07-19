<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ToolkitComponentPageTest extends WebTestCase
{
    public function testComponentPageRendersReadmeTabsAndApiTable(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/toolkit/kits/shadcn/components/accordion');

        self::assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        // Preview/Code tabs render via the site's Tabs component (override of the Toolkit base tabs).
        self::assertGreaterThan(0, $crawler->filter('.Wysiwyg [data-controller="tabs"]')->count());
        // Fenced code renders with the site's syntax highlighter (Tempest hl-* spans), not plain code.
        self::assertSelectorExists('.Wysiwyg .hl-keyword');
        // API reference renders as a real table (TableExtension), not raw pipes.
        self::assertSelectorExists('.Wysiwyg table');
        // Prop descriptions render as a popover (the `(?)[...]` syntax -> the site's Tooltip).
        self::assertStringContainsString('id="prop-description-1"', $content);
        // The README owns the single page title — no duplicate chrome <h1>.
        self::assertCount(1, $crawler->filter('.Wysiwyg h1'));
        // ToC keeps inner HTML like <code> (e.g. for the `<twig:Accordion>` heading).
        self::assertSelectorExists('nav[data-controller="toolkit-recipe-toc"] code');
    }
}
