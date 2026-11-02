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

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Test\HasBrowser;

final class CssPageTest extends KernelTestCase
{
    use HasBrowser;

    public function testPackagePage(): void
    {
        $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->assertSeeIn('title', 'UX CSS - Typed Atomic CSS for Twig')
            ->assertSeeIn('h1', 'checked when Twig compiles')
            ->assertSee('composer require symfony/ux-css')
            ->assertSeeElement('link[rel="stylesheet"][href*="/ux_css/styles"]')
        ;
    }

    public function testDemoCardIsStyledWithCss(): void
    {
        $crawler = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler();

        $card = $crawler->filter('#demo [data-css-demo-target="preview"] article');
        self::assertCount(1, $card);
        self::assertSame('p_lg bdr_md bg_surface c_fg', $card->attr('class'));
        self::assertSame('d_inline-block mt_md px_md py_sm bdr_md bg_blue.600 c_white hover:bg_blue.700', $card->filter('a')->attr('class'));
    }

    #[DataProvider('provideColoredCodeBlocks')]
    public function testCodeBlockShowsTheColorOfTokens(string $path, string $swatch): void
    {
        $crawler = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler();

        $block = $crawler->filter('.Terminal')->reduce(
            static fn (Crawler $terminal): bool => str_contains($terminal->text(), $path) && $terminal->filter('.hl-swatch')->count() > 0,
        );
        self::assertCount(1, $block);

        $swatches = $block->filter('.hl-swatch[style]')->each(static fn (Crawler $swatch): string => $swatch->attr('style'));
        self::assertContains($swatch, $swatches);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideColoredCodeBlocks(): iterable
    {
        yield 'tokens config' => ['config/packages/ux_css.yaml', '--swatch: #1572b6'];
        yield 'demo card' => ['templates/ux_packages/css/_card.html.twig', '--swatch: var(--colors-blue-600)'];
        yield 'css variables highlight' => ['assets/styles/app.css', '--swatch: var(--colors-blue-600)'];
    }

    public function testEditorHighlightShowsPhpStormScreenshots(): void
    {
        $crawler = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler();

        $screenshots = $crawler->filter('figure img[src*="/images/ux_packages/css/phpstorm-"]');
        self::assertCount(4, $screenshots);

        $screenshots->each(static function (Crawler $screenshot): void {
            self::assertNotEmpty($screenshot->attr('alt'));
            self::assertSame($screenshot->attr('src'), $screenshot->closest('a')?->attr('href'));
            self::assertSame('890', $screenshot->attr('width'));
            self::assertContains($screenshot->attr('height'), ['553', '910']);
        });
    }

    public function testEditorHighlightLinksToTheEditorPlugins(): void
    {
        self::assertSame([
            'https://plugins.jetbrains.com/plugin/7219-symfony-plugin',
            'https://marketplace.visualstudio.com/items?itemName=symfony.language-tools',
        ], $this->highlight('Typed for your editor')->filter('p a')->each(static fn (Crawler $link): string => $link->attr('href')));
    }

    public function testHeadingsNameTheBundle(): void
    {
        $headings = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler()
            ->filter('h2')
            ->each(static fn (Crawler $heading): string => $heading->text());

        foreach (['Add UX CSS to your app.', 'Install UX CSS.', 'Keep UX CSS at hand.'] as $heading) {
            self::assertContains($heading, $headings);
        }
    }

    public function testWritingStylesComesBeforeTooling(): void
    {
        $headings = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler()
            ->filter('h2')
            ->each(static fn (Crawler $heading): string => $heading->text());

        self::assertLessThan(
            array_search('Mistakes never reach production.', $headings, true),
            array_search('From tokens to one stylesheet.', $headings, true),
        );
    }

    /**
     * @param list<string> $highlights
     */
    #[DataProvider('provideHighlightSections')]
    public function testHighlightsAreGroupedBySection(string $title, array $highlights): void
    {
        $section = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler()
            ->filter('section')
            ->reduce(static fn (Crawler $section): bool => $title === $section->filter('h2')->text(''));
        self::assertCount(1, $section);

        self::assertSame($highlights, $section->filter('article h3')->each(static fn (Crawler $heading): string => $heading->text()));
    }

    /**
     * @return iterable<string, array{0: string, 1: list<string>}>
     */
    public static function provideHighlightSections(): iterable
    {
        yield 'mistakes' => ['Mistakes never reach production.', [
            'A typo is not a missing style',
            'Typed for your editor',
        ]];
        yield 'writing styles' => ['From tokens to one stylesheet.', [
            'Tokens as CSS variables',
            'Conditions and breakpoints',
            'Values from props',
            'One stylesheet, written by PHP',
        ]];
    }

    #[DataProvider('provideCodeInProse')]
    public function testProseFormatsCodeAsCode(string $snippet): void
    {
        $crawler = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler();

        $prose = $crawler->filter('p, figcaption')->reduce(static fn (Crawler $node): bool => str_contains($node->text(), $snippet));
        self::assertGreaterThan(0, $prose->count());

        $prose->each(static function (Crawler $node) use ($snippet): void {
            $inCode = implode(' ', $node->filter('code')->each(static fn (Crawler $code): string => $code->text()));
            self::assertSame(substr_count($node->text(), $snippet), substr_count($inCode, $snippet), $node->text());
        });
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideCodeInProse(): iterable
    {
        yield 'twig function' => ['css()'];
        yield 'lint command' => ['lint:twig'];
        yield 'warmup command' => ['cache:warmup'];
    }

    public function testPropsHighlightPassesAPropToCss(): void
    {
        $code = $this->highlight('Values from props')->filter('.Terminal pre')->text();

        self::assertStringContainsString("{% props tone = 'blue.600' %}", $code);
        self::assertStringContainsString('bg: tone,', $code);
    }

    public function testStylesheetHighlightShowsAssetMapperAndBundlerSetups(): void
    {
        $highlight = $this->highlight('One stylesheet, written by PHP');

        $block = $highlight->filter('.Terminal pre');
        self::assertCount(1, $block);
        self::assertStringContainsString('assets/styles/app.css', $block->text());
        self::assertSame([
            '@layer reset, base, tokens, components, utilities;',
            "@import '../../var/ux_css/styles.css';",
        ], $block->filter('.hl-addition')->each(static fn (Crawler $line): string => $line->text()));
        self::assertCount(1, $block->filter('.hl-addition .hl-keyword'));
        self::assertStringContainsString("\n@import '../../var/ux_css/styles.css';\n", $highlight->filter('[data-clipboarder-source-value]')->attr('data-clipboarder-source-value'));

        self::assertSame([
            'https://symfony.com/doc/current/frontend/asset_mapper.html',
            'https://symfony.com/bundles/reprise/current/index.html',
            'https://symfony.com/doc/current/frontend/encore/index.html',
        ], $highlight->filter('p a')->each(static fn (Crawler $link): string => $link->attr('href')));
    }

    private function highlight(string $title): Crawler
    {
        $highlight = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler()
            ->filter('article')
            ->reduce(static fn (Crawler $article): bool => $title === $article->filter('h3')->text(''));
        self::assertCount(1, $highlight);

        return $highlight;
    }

    public function testDemoShowsTheRealCompileErrors(): void
    {
        $crawler = $this->browser()
            ->visit('/css')
            ->assertSuccessful()
            ->crawler();

        $code = $crawler->filter('#demo [data-css-mistakes] pre')->text();

        foreach ([
            '{# Unknown property "colr". Did you mean "color"? #}',
            '{# Unknown colors token "surfac". Did you mean "surface"? #}',
            '{# Invalid value "flexx" for "display". Did you mean "flex"? #}',
            '{# Unknown condition "_hovr". Did you mean "_hover"? #}',
            '{# Unknown sizes token "37ch". Write a raw value between brackets, like "[37ch]". #}',
        ] as $message) {
            self::assertStringContainsString($message, $code);
        }
    }
}
