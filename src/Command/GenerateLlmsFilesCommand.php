<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Enum\ToolkitKitId;
use App\Service\Changelog\ChangelogProvider;
use App\Service\CookbookRepository;
use App\Service\LiveDemoRepository;
use App\Service\Toolkit\ToolkitService;
use App\Service\TurboDemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Twig\Environment;

#[AsCommand(name: 'app:generate-llms-files', description: 'Generates llms.txt and Markdown files for LLM consumption')]
class GenerateLlmsFilesCommand
{
    private Filesystem $fs;
    private string $publicDir;
    private string $outputDir;

    public function __construct(
        private readonly Environment $twig,
        private readonly UxPackageRepository $packageRepository,
        private readonly LiveDemoRepository $liveDemoRepository,
        private readonly TurboDemoRepository $turboDemoRepository,
        private readonly CookbookRepository $cookbookRepository,
        private readonly ChangelogProvider $changelogProvider,
        private readonly ToolkitService $toolkitService,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%app.llms_dir%')] private readonly string $llmsDir,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->fs = new Filesystem();
        $this->publicDir = $this->projectDir.'/public';
        $this->outputDir = $this->llmsDir;

        $this->cleanup($io);

        [$toolkitContent, $toolkitKits] = $this->generateToolkit($io);

        $allContent = array_merge(
            $this->generatePackagesListing($io),
            $this->generateDocumentation($io),
            $this->generatePackages($io),
            $this->generateDemos($io),
            $this->generateCookbook($io),
            $this->generateChangelog($io),
            $toolkitContent,
        );
        $this->generateLlmsFiles($io, $allContent, $toolkitKits);

        $io->success('All LLM files generated successfully.');

        return Command::SUCCESS;
    }

    private function cleanup(SymfonyStyle $io): void
    {
        $io->section('Cleaning up previously generated files');

        // Remove all generated Markdown files
        $this->fs->remove($this->outputDir);

        // Remove llms.txt and llms-full.txt from public/
        $this->fs->remove([
            $this->publicDir.'/llms.txt',
            $this->publicDir.'/llms-full.txt',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function generatePackagesListing(SymfonyStyle $io): array
    {
        $io->section('Generating packages listing Markdown file');

        $md = $this->twig->render('llms/packages.md.twig', [
            'packages' => $this->packageRepository->findAll(removed: false, sortByName: true),
            'removed_packages' => $this->packageRepository->findAll(removed: true, sortByName: true),
        ]);
        $path = $this->generateMdPath('app_packages');
        $this->fs->dumpFile($this->outputDir.'/'.$path, $md);

        return [$path => $md];
    }

    /**
     * @return array<string, string>
     */
    private function generateDocumentation(SymfonyStyle $io): array
    {
        $io->section('Generating documentation Markdown file');

        $md = $this->twig->render('llms/documentation.md.twig', [
            'packages' => $this->packageRepository->findAll(removed: false, sortByName: true),
            'removed_packages' => $this->packageRepository->findAll(removed: true, sortByName: true),
        ]);
        $path = $this->generateMdPath('app_documentation');
        $this->fs->dumpFile($this->outputDir.'/'.$path, $md);

        return [$path => $md];
    }

    /**
     * @return array<string, string>
     */
    private function generatePackages(SymfonyStyle $io): array
    {
        $io->section('Generating package Markdown files');
        $content = [];

        foreach ($this->packageRepository->findAll(removed: false, sortByName: true) as $package) {
            $md = $this->twig->render('llms/package.md.twig', ['package' => $package]);
            $path = $this->generateMdPath($package->getRoute());
            $this->fs->dumpFile($this->outputDir.'/'.$path, $md);
            $content[$path] = $md;
            $io->writeln('  '.$package->getHumanName());
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    private function generateDemos(SymfonyStyle $io): array
    {
        $io->section('Generating demos Markdown file');

        $md = $this->twig->render('llms/demos.md.twig', [
            'demos' => array_merge(
                $this->liveDemoRepository->findAll(),
                $this->turboDemoRepository->findAll(),
            ),
        ]);
        $path = $this->generateMdPath('app_demos');
        $this->fs->dumpFile($this->outputDir.'/'.$path, $md);

        return [$path => $md];
    }

    /**
     * @return array<string, string>
     */
    private function generateCookbook(SymfonyStyle $io): array
    {
        $io->section('Generating cookbook Markdown files');
        $content = [];
        $cookbooks = $this->cookbookRepository->findAll();

        if (0 === \count($cookbooks)) {
            return $content;
        }

        foreach ($cookbooks as $cookbook) {
            $md = $this->twig->render('llms/cookbook.md.twig', ['cookbook' => $cookbook]);
            $path = $this->generateMdPath('app_cookbook_show', ['slug' => $cookbook->slug]);
            $this->fs->dumpFile($this->outputDir.'/'.$path, $md);
            $content[$path] = $md;
            $io->writeln('  '.$cookbook->title);
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    private function generateChangelog(SymfonyStyle $io): array
    {
        $io->section('Generating changelog Markdown file');

        $releases = [];
        for ($page = 1; $page <= 3; ++$page) {
            $pageReleases = $this->changelogProvider->getChangelog($page);
            if (empty($pageReleases)) {
                break;
            }
            $releases = array_merge($releases, $pageReleases);
        }

        $md = $this->twig->render('llms/changelog.md.twig', ['releases' => $releases]);
        $path = $this->generateMdPath('app_changelog');
        $this->fs->dumpFile($this->outputDir.'/'.$path, $md);

        return [$path => $md];
    }

    /**
     * @return array{array<string, string>, list<array{kitId: ToolkitKitId, kit: object}>}
     */
    private function generateToolkit(SymfonyStyle $io): array
    {
        $io->section('Generating toolkit Markdown files');
        $content = [];
        $toolkitKits = [];

        foreach (ToolkitKitId::cases() as $kitId) {
            $kit = $this->toolkitService->getKit($kitId);
            $toolkitKits[] = ['kitId' => $kitId, 'kit' => $kit];

            foreach ($kit->getRecipes(RecipeType::Component) as $recipe) {
                $apiRef = $this->toolkitService->extractRecipeApiReference($recipe);
                $md = $this->twig->render('llms/toolkit_component.md.twig', [
                    'kitId' => $kitId,
                    'kit' => $kit,
                    'recipe' => $recipe,
                    'apiRef' => $apiRef,
                ]);
                $path = $this->generateMdPath('app_toolkit_component', [
                    'kitId' => $kitId->value,
                    'componentName' => $recipe->name,
                ]);
                $this->fs->dumpFile($this->outputDir.'/'.$path, $md);
                $content[$path] = $md;
                $io->writeln('  ['.$kitId->value.'] '.$recipe->name);
            }
        }

        return [$content, $toolkitKits];
    }

    /**
     * @param array<string, string>                         $allContent
     * @param list<array{kitId: ToolkitKitId, kit: object}> $toolkitKits
     */
    private function generateLlmsFiles(SymfonyStyle $io, array $allContent, array $toolkitKits): void
    {
        $packages = $this->packageRepository->findAll(removed: false, sortByName: true);
        $cookbooks = $this->cookbookRepository->findAll();

        // Homepage (index.html.md) and llms.txt share the same content
        $io->section('Generating llms.txt and homepage Markdown');
        $llmsTxt = $this->twig->render('llms/llms.txt.twig', [
            'packages' => $packages,
            'cookbooks' => $cookbooks,
            'toolkitKits' => $toolkitKits,
        ]);
        $homepagePath = $this->generateMdPath('app_homepage');
        $this->fs->dumpFile($this->outputDir.'/'.$homepagePath, $llmsTxt);
        $this->fs->dumpFile($this->publicDir.'/llms.txt', $llmsTxt);
        $allContent[$homepagePath] = $llmsTxt;

        // llms-full.txt aggregates all content
        $io->section('Generating llms-full.txt');
        $llmsFull = $this->twig->render('llms/llms_full.txt.twig', [
            'llmsIndex' => $llmsTxt,
            'allContent' => $allContent,
        ]);
        $this->fs->dumpFile($this->publicDir.'/llms-full.txt', $llmsFull);
    }

    /**
     * Converts a route URL to the corresponding .md file path relative to the output directory.
     *
     * "/" becomes "index.html.md", "/foo/bar" becomes "foo/bar.md".
     *
     * @param array<string, string> $parameters
     */
    private function generateMdPath(string $route, array $parameters = []): string
    {
        $url = $this->urlGenerator->generate($route, $parameters);

        if ('/' === $url) {
            return 'index.html.md';
        }

        return ltrim($url, '/').'.md';
    }
}
