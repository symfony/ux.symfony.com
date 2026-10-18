<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Parser\MarkdownParser;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Exposes each Toolkit recipe README as a template, `@ToolkitPreview/<kit>/<recipe>`, with one block per
 * `{"preview": true}` example: `preview_1`, `preview_2`... in reading order. The README is only read when
 * Twig compiles the template.
 */
final class ToolkitPreviewLoader implements LoaderInterface
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/vendor/symfony/ux-toolkit/kits')]
        private readonly string $kitsDir,
    ) {
    }

    public function getSourceContext(string $name): Source
    {
        $path = $this->findReadme($name);

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $source = '';
        $number = 0;
        foreach ((new MarkdownParser($environment))->parse(file_get_contents($path))->iterator() as $node) {
            if ($node instanceof FencedCode && preg_match('/^\S+\s+(\{.*\})$/s', $node->getInfo() ?? '', $matches) && true === (json_decode($matches[1], true)['preview'] ?? null)) {
                $source .= \sprintf("{%% block preview_%d %%}%s{%% endblock %%}\n", ++$number, $node->getLiteral());
            }
        }

        return new Source($source, $name, $path);
    }

    public function getCacheKey(string $name): string
    {
        return $name;
    }

    public function isFresh(string $name, int $time): bool
    {
        return filemtime($this->findReadme($name)) < $time;
    }

    public function exists(string $name): bool
    {
        return null !== $this->readmePath($name);
    }

    private function findReadme(string $name): string
    {
        return $this->readmePath($name) ?? throw new LoaderError(\sprintf('No Toolkit recipe README for "%s".', $name));
    }

    private function readmePath(string $name): ?string
    {
        if (!preg_match('#^@ToolkitPreview/([\w-]+)/([\w-]+)$#', $name, $matches)) {
            return null;
        }

        $path = \sprintf('%s/%s/%s/README.md', $this->kitsDir, $matches[1], $matches[2]);

        return is_file($path) ? $path : null;
    }
}
