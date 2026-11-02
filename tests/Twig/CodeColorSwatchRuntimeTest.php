<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig;

use App\Twig\Extension\CodeColorSwatchRuntime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CodeColorSwatchRuntimeTest extends KernelTestCase
{
    #[DataProvider('provideColoredLines')]
    public function testLineWithAColorGetsASwatch(string $line, string $color): void
    {
        self::assertSame(
            '<span class="hl-swatch" style="--swatch: '.$color.'" aria-hidden="true"></span>'.$line,
            $this->runtime()->addSwatches($line),
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideColoredLines(): iterable
    {
        yield 'hex value' => ['600: <span class="hl-value">&#039;#1572b6&#039;</span>', '#1572b6'];
        yield 'color token' => ["bg: 'blue.600',", 'var(--colors-blue-600)'];
        yield 'semantic color token' => ["color: 'fg',", 'var(--colors-fg)'];
        yield 'token reference' => ["base: '{colors.gray.900}'", 'var(--colors-gray-900)'];
        yield 'css variable' => ['color: var(--colors-blue-600);', 'var(--colors-blue-600)'];
        yield 'first color of the line' => ["_hover: { bg: 'blue.700', color: 'white' },", 'var(--colors-blue-700)'];
        yield 'oklch value' => ['500: <span class="hl-value">&#039;oklch(0.62 0.21 259)&#039;</span>', 'oklch(0.62 0.21 259)'];
        yield 'oklch value between brackets' => ["color: '[oklch(62% 0.2 250 / 50%)]',", 'oklch(62% 0.2 250 / 50%)'];
        yield 'oklch value in a css declaration' => ['--colors-brand: oklch(0.62 0.21 259);', 'oklch(0.62 0.21 259)'];
        yield 'oklab value' => ["brand: 'oklab(0.62 -0.05 -0.2)'", 'oklab(0.62 -0.05 -0.2)'];
        yield 'lch value' => ["brand: 'lch(52% 72 259)'", 'lch(52% 72 259)'];
        yield 'lab value' => ["brand: 'lab(52% -10 -60)'", 'lab(52% -10 -60)'];
        yield 'hwb value' => ["brand: 'hwb(220 10% 20%)'", 'hwb(220 10% 20%)'];
        yield 'hsl value' => ["brand: 'hsl(220deg 90% 50% / 0.5)'", 'hsl(220deg 90% 50% / 0.5)'];
        yield 'rgb value' => ["brand: 'rgb(21 114 182)'", 'rgb(21 114 182)'];
        yield 'legacy rgba value' => ["brand: 'rgba(21, 114, 182, 0.5)'", 'rgba(21, 114, 182, 0.5)'];
        yield 'color function' => ["brand: 'color(display-p3 0.1 0.45 0.7)'", 'color(display-p3 0.1 0.45 0.7)'];
        yield 'uppercase function name' => ["brand: 'OKLCH(0.62 0.21 259)'", 'OKLCH(0.62 0.21 259)'];
    }

    #[DataProvider('provideLinesWithoutColor')]
    public function testLineWithoutColorGetsAnEmptyGutter(string $line): void
    {
        self::assertSame(
            '<span class="hl-swatch" aria-hidden="true"></span>'.$line,
            $this->runtime()->addSwatches($line),
        );
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideLinesWithoutColor(): iterable
    {
        yield 'spacing token' => ["mt: 'md',"];
        yield 'unknown token' => ["bg: 'surfac',"];
        yield 'twig comment' => ['{# Unknown colors token #}'];
        yield 'empty line' => [''];
        yield 'function that is not a color' => ["transform: 'rotate(45deg)',"];
        yield 'color function that closes the declaration' => ["brand: 'rgb(0 0 0;background:red)'"];
    }

    public function testEveryLineGetsAGutter(): void
    {
        $swatches = $this->runtime()->addSwatches("bg: 'blue.600',\nmt: 'md',");

        self::assertSame(
            '<span class="hl-swatch" style="--swatch: var(--colors-blue-600)" aria-hidden="true"></span>bg: \'blue.600\','
            ."\n".'<span class="hl-swatch" aria-hidden="true"></span>mt: \'md\',',
            $swatches,
        );
    }

    private function runtime(): CodeColorSwatchRuntime
    {
        return self::getContainer()->get(CodeColorSwatchRuntime::class);
    }
}
