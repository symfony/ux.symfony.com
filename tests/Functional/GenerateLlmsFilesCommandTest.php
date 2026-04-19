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

use App\Enum\ToolkitKitId;
use App\Service\CookbookRepository;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\Test\HasBrowser;

class GenerateLlmsFilesCommandTest extends KernelTestCase
{
    use HasBrowser;

    private static bool $commandExecuted = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$commandExecuted) {
            $kernel = self::bootKernel();
            $application = new Application($kernel);
            $command = $application->find('app:generate-llms-files');
            $tester = new CommandTester($command);
            $tester->execute([]);

            self::assertSame(0, $tester->getStatusCode(), 'Command should exit with code 0');
            self::assertStringContainsString('All LLM files generated successfully', $tester->getDisplay());

            self::$commandExecuted = true;
        }
    }

    public function testCommandGeneratesLlmsFilesInPublicDir()
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        self::assertFileExists($projectDir.'/public/llms.txt');
        self::assertFileExists($projectDir.'/public/llms-full.txt');
    }

    public function testCommandGeneratesMarkdownFilesInLlmsDir()
    {
        $llmsDir = self::getContainer()->getParameter('app.llms_dir');

        // Core files
        self::assertFileExists($llmsDir.'/index.html.md');
        self::assertFileExists($llmsDir.'/packages.md');
        self::assertFileExists($llmsDir.'/documentation.md');
        self::assertFileExists($llmsDir.'/demos.md');
        self::assertFileExists($llmsDir.'/changelog.md');

        // Package files
        $packageRepository = new UxPackageRepository();
        foreach ($packageRepository->findAll(removed: false) as $package) {
            self::assertFileExists($llmsDir.'/'.$package->getName().'.md');
        }

        // Cookbook files
        $cookbookRepository = self::getContainer()->get(CookbookRepository::class);
        foreach ($cookbookRepository->findAll() as $cookbook) {
            self::assertFileExists($llmsDir.'/cookbook/'.$cookbook->slug.'.md');
        }

        // Toolkit files
        foreach (ToolkitKitId::cases() as $kitId) {
            self::assertDirectoryExists($llmsDir.'/toolkit/kits/'.$kitId->value.'/components');
        }
    }

    #[DataProvider('provideMarkdownUrls')]
    public function testMarkdownIsAccessible(string $htmlUrl, string $mdUrl)
    {
        // .md URL suffix returns markdown
        $this->browser()
            ->visit($mdUrl)
            ->assertSuccessful()
            ->assertContentType('markdown')
        ;

        // Content negotiation with Accept: text/markdown
        $this->browser()
            ->get($htmlUrl, HttpOptions::create()->withHeader('Accept', 'text/markdown'))
            ->assertSuccessful()
            ->assertContentType('markdown')
        ;

        // HTML is preferred when listed before markdown
        $this->browser()
            ->get($htmlUrl, HttpOptions::create()->withHeader('Accept', 'text/html, text/markdown'))
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'text/html')
        ;
    }

    public function testPathTraversalIsBlocked()
    {
        $this->browser()
            ->visit('/../../etc/passwd.md')
            ->assertStatus(404)
        ;

        $this->browser()
            ->get('/../../../etc/passwd', HttpOptions::create()->withHeader('Accept', 'text/markdown'))
            ->assertStatus(404)
        ;
    }

    public static function provideMarkdownUrls(): \Generator
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer()->get('test.service_container');
        $router = $container->get(UrlGeneratorInterface::class);

        // Core pages
        $homepageUrl = $router->generate('app_homepage');
        yield 'homepage' => [$homepageUrl, '/index.html.md'];

        foreach (['app_packages', 'app_documentation', 'app_demos', 'app_changelog'] as $route) {
            $url = $router->generate($route);
            yield $route => [$url, $url.'.md'];
        }

        // All package pages
        $packageRepository = new UxPackageRepository();
        foreach ($packageRepository->findAll(removed: false) as $package) {
            $url = $router->generate($package->getRoute());
            yield $package->getName() => [$url, $url.'.md'];
        }

        // Cookbook pages
        $cookbookRepository = $container->get(CookbookRepository::class);
        foreach ($cookbookRepository->findAll() as $cookbook) {
            $url = $router->generate('app_cookbook_show', ['slug' => $cookbook->slug]);
            yield 'cookbook/'.$cookbook->slug => [$url, $url.'.md'];
        }

        // Toolkit component pages
        $toolkitService = $container->get(ToolkitService::class);
        foreach (ToolkitKitId::cases() as $kitId) {
            $kit = $toolkitService->getKit($kitId);
            foreach ($kit->getRecipes(RecipeType::Component) as $recipe) {
                $url = $router->generate('app_toolkit_component', [
                    'kitId' => $kitId->value,
                    'componentName' => $recipe->name,
                ]);
                yield $kitId->value.'/'.$recipe->name => [$url, $url.'.md'];
            }
        }
    }
}
