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

    public function testComponentPreviewLoadsTheEntrypointOfItsKit(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/toolkit/kits/flowbite-4/components/alert');
        $previewUrl = $crawler->filter('[data-lazy-iframe-src-value]')->first()->attr('data-lazy-iframe-src-value');

        $client->request('GET', $previewUrl);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString("import 'ux-toolkit-flowbite-4'", $client->getResponse()->getContent());
    }

    public function testComponentPreviewRendersInStandardsMode(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/toolkit/kits/shadcn/components/accordion');
        $previewUrl = $crawler->filter('[data-lazy-iframe-src-value]')->first()->attr('data-lazy-iframe-src-value');

        $client->request('GET', $previewUrl);

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('<!DOCTYPE html>', ltrim($client->getResponse()->getContent()));
    }

    public function testComponentPreviewIsCacheableAndPointsToItsPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/toolkit/kits/shadcn/components/button?preview=2');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Link', '<http://localhost/toolkit/kits/shadcn/components/button>; rel="canonical"');
        self::assertResponseHeaderSame('Cache-Control', 'max-age=86400, public');
        self::assertResponseHasHeader('ETag');
    }

    public function testComponentPreviewBeyondTheExamplesIsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/toolkit/kits/shadcn/components/button?preview=999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testBlockPreviewIsServedByItsSection(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/toolkit/kits/shadcn/blocks/login');

        self::assertSame(
            ['http://localhost/toolkit/kits/shadcn/blocks/login?preview=login-01', 'http://localhost/toolkit/kits/shadcn/blocks/login?preview=login-02'],
            $crawler->filter('[data-lazy-iframe-src-value]')->each(static fn ($node) => $node->attr('data-lazy-iframe-src-value')),
        );

        $client->request('GET', '/toolkit/kits/shadcn/blocks/login?preview=login-02');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Link', '<http://localhost/toolkit/kits/shadcn/blocks/login>; rel="canonical"');

        $client->request('GET', '/toolkit/kits/shadcn/blocks/login?preview=button');
        self::assertResponseStatusCodeSame(404);
    }

    public function testToolkitDemoPreviewRendersWithTheKit(): void
    {
        $client = static::createClient();
        $client->request('GET', '/toolkit?preview=demo');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString("import 'ux-toolkit-shadcn'", $client->getResponse()->getContent());
    }

    public function testFormerSignedPreviewUrlIsGone(): void
    {
        $client = static::createClient();
        $client->request('GET', '/toolkit/component_preview?kitId=shadcn&code=%3Cp%3E&_hash=x');

        self::assertResponseStatusCodeSame(410);
    }
}
