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

use App\Service\UxPackageRepository;
use Playwright\Playwright;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:generate-og-images', description: 'Generates the 1200x675 Open Graph PNG images for every UX package')]
class GenerateOgImagesCommand
{
    public function __construct(
        private readonly UxPackageRepository $packageRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Base URL of the running Symfony dev server')] string $baseUrl = 'https://127.0.0.1:9044',
        #[Option(description: 'Generate the image of a single package only')] ?string $package = null,
    ): int {
        $baseUrl = rtrim($baseUrl, '/');
        $outputDir = $this->projectDir.'/assets/images/ux_packages';

        if (!$this->isReachable($baseUrl)) {
            $io->error(\sprintf('Dev server is not reachable at %s. Start it with "symfony serve -d" first.', $baseUrl));

            return Command::FAILURE;
        }

        $packages = null !== $package
            ? [$this->packageRepository->find($package)]
            : $this->packageRepository->findAll();

        $io->section(\sprintf('Generating %d OG image(s)', \count($packages)));

        try {
            $context = Playwright::chromium([
                'headless' => true,
                'context' => [
                    'viewport' => ['width' => 1200, 'height' => 675],
                    'deviceScaleFactor' => 1,
                    'reducedMotion' => 'reduce',
                ],
            ]);
        } catch (\Throwable $e) {
            $io->error('Could not launch Chromium via Playwright: '.$e->getMessage());
            $io->writeln('Install browser binaries with: <info>vendor/bin/playwright-install --browsers</info>');

            return Command::FAILURE;
        }

        try {
            $page = $context->newPage();

            foreach ($packages as $uxPackage) {
                $path = $this->urlGenerator->generate('app_og_image_show', ['packageName' => $uxPackage->getName()]);
                $url = $baseUrl.$path;

                $io->writeln(\sprintf('  <info>%s</info> → %s', $uxPackage->getName(), $url));

                $page->goto($url, ['waitUntil' => 'networkidle']);
                $page->waitForSelector('body[data-og-ready="true"]', ['timeout' => 15000]);
                $page->screenshot(
                    \sprintf('%s/%s-1200x675.png', $outputDir, $uxPackage->getName()),
                    [
                        'type' => 'png',
                        'clip' => ['x' => 0, 'y' => 0, 'width' => 1200, 'height' => 675],
                    ],
                );
            }
        } finally {
            $context->close();
        }

        $io->success(\sprintf('Generated %d OG image(s) in %s', \count($packages), $outputDir));

        return Command::SUCCESS;
    }

    private function isReachable(string $baseUrl): bool
    {
        try {
            $this->httpClient->request('GET', $baseUrl.'/', ['timeout' => 3])->getStatusCode();
        } catch (TransportExceptionInterface) {
            return false;
        }

        return true;
    }
}
