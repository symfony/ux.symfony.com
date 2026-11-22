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

use App\EventListener\SlowDemoUploadListener;
use App\EventListener\UploadDemoQuotaListener;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Zenstruck\Browser\Test\HasBrowser;

final class UploadDemosTest extends KernelTestCase
{
    use HasBrowser;

    private const DEMOS = [
        '/demos/upload/keep-files' => 'Keep Files on Errors',
        '/demos/upload/turbo' => 'Upload with Turbo',
        '/demos/upload/live-avatar' => 'Live Avatar',
        '/demos/upload/input' => 'Simple & Enhanced Input',
        '/demos/upload/dropzone' => 'Dropzone & Card Grid',
        '/demos/upload/resumable' => 'Resumable Large Files',
    ];

    /**
     * The first and the last line of every excerpt cut by line numbers:
     * a range that shifted shows half a statement, or nothing.
     */
    private const EXCERPTS = [
        '/demos/upload/keep-files' => [
            'src/Form/KeepFilesForm.php' => ['$builder', ';'],
            'src/Controller/Demo/Upload/KeepFilesDemoController.php' => ['$form = $this->createForm(KeepFilesForm::class);', '}'],
        ],
        '/demos/upload/turbo' => [
            'src/Controller/Demo/Upload/TurboUploadDemoController.php' => ['if ($form->isSubmitted() && $form->isValid()) {', '}'],
        ],
        '/demos/upload/live-avatar' => [
            'src/Twig/Components/UploadAvatar.php' => ['#[AsLiveComponent]', '}'],
            'src/Form/AvatarUploadForm.php' => ['$builder', ';'],
        ],
        '/demos/upload/input' => [
            'src/Form/UploadSimpleInputForm.php' => ["\$builder->add('document', UploadType::class, [", ']);'],
            'src/Form/UploadEnhancedInputForm.php' => ["\$builder->add('photos', UploadType::class, [", ']);'],
        ],
        '/demos/upload/dropzone' => [
            'src/Form/UploadDropzoneForm.php' => ["\$builder->add('documents', UploadType::class, [", ']);'],
            'src/Form/UploadCardGridForm.php' => ["\$builder->add('photos', UploadType::class, [", ']);'],
        ],
        '/demos/upload/resumable' => [
            'config/packages/ux_upload.yaml' => ['large:', 'parallel_chunks: 2'],
            'src/Form/UploadLargeFileForm.php' => ["\$builder->add('file', UploadType::class, [", ']);'],
            'assets/controllers/upload-chunks-controller.js' => ['chunk(event) {', '}'],
            'src/EventListener/SlowDemoUploadListener.php' => ['#[AsEventListener(event: KernelEvents::REQUEST)]', '}'],
        ],
    ];

    public function testDemosIndex(): void
    {
        $page = $this->browser()
            ->visit('/demos/upload')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Upload demos')
        ;

        // The same grid as the index of the Pagination demos
        self::assertSame(6, $page->crawler()->filter('.DemoCardGrid > .Card')->count());
        self::assertSame('/demos/upload/keep-files', $page->crawler()->filter('.Card a')->first()->attr('href'), 'The flagship demo comes first.');
        self::assertSame('Upload demos - Symfony UX', $page->crawler()->filter('title')->text());
    }

    #[DataProvider('provideDemos')]
    public function testDemoRenders(string $url, string $name): void
    {
        $crawler = $this->browser()->visit($url)->assertSuccessful()->assertSeeIn('h1', $name)->crawler();

        self::assertSame(1, $crawler->filter('.eyebrows a[href$="/demos/upload"]')->count());
        self::assertStringEndsWith($url, $crawler->filter('link[rel="canonical"]')->attr('href'));
        self::assertGreaterThanOrEqual(1, $crawler->filter('input[type="file"]')->count());
        self::assertGreaterThanOrEqual(1, $crawler->filter('[data-controller="upload-samples"]')->count(), 'Every demo starts with a sample file.');
        self::assertCount(1, $crawler->filter('.demo-introduction a[href="/upload"]'), 'The introduction links to the package page.');

        // A social card cuts its text at 120 characters: the short description reaches it whole
        $social = (string) $crawler->filter('meta[property="og:description"]')->attr('content');
        self::assertStringNotContainsString("\n", $social);
        self::assertStringEndsNotWith('...', $social);
    }

    #[DataProvider('provideDemoUrls')]
    public function testEveryCodeExcerptShowsWholeStatements(string $url): void
    {
        $crawler = $this->browser()->visit($url)->assertSuccessful()->crawler();

        $excerpts = [];
        foreach ($crawler->filter('.UploadDemoStory .Terminal') as $terminal) {
            $terminal = new Crawler($terminal);
            // An excerpt cut by line numbers shows them in a gutter
            $lines = array_map(static fn (string $line): string => trim((string) preg_replace('/^\s*\d+/', '', $line)), explode("\n", $terminal->filter('.Terminal_content')->text(null, false)));
            $lines = array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
            self::assertNotEmpty($lines, 'An excerpt is empty on '.$url);
            $excerpts[] = [$terminal->filter('.Terminal_header')->text(), $lines];
        }
        self::assertGreaterThanOrEqual(3, \count($excerpts), 'A demo explains itself in three steps at least.');
        self::assertCount(\count($excerpts), $crawler->filter('.UploadDemoStory .code-description h2'), 'Every excerpt comes with a titled explanation.');

        foreach (self::EXCERPTS[$url] as $file => [$first, $last]) {
            $found = array_values(array_filter($excerpts, static fn (array $excerpt): bool => str_contains($excerpt[0], $file)));
            self::assertCount(1, $found, $file);
            self::assertSame($first, $found[0][1][0], $file);
            self::assertSame($last, end($found[0][1]), $file);
        }
    }

    #[DataProvider('provideDemoUrls')]
    public function testADemoLinksToThePreviousAndTheNextOne(string $url): void
    {
        $urls = array_keys(self::DEMOS);
        $position = (int) array_search($url, $urls, true);
        $count = \count($urls);

        $links = $this->browser()->visit($url)->assertSuccessful()->crawler()
            ->filter('aside nav[aria-label="More demos"] .Card a')
            ->each(static fn (Crawler $link): string => (string) $link->attr('href'));

        self::assertSame([$urls[($position - 1 + $count) % $count], $urls[($position + 1) % $count]], $links);
    }

    #[DataProvider('provideDemos')]
    public function testDemoIsDiscoverable(string $url, string $name): void
    {
        foreach (['/demos', '/demos/upload', '/upload'] as $listing) {
            $page = $this->browser()->visit($listing)->assertSuccessful();

            self::assertSame(1, $page->crawler()->filter(\sprintf('.Card a[href="%s"]', $url))->count(), $name.' on '.$listing);
        }

        self::assertStringContainsString($url.'</loc>', $this->browser()->visit('/sitemap.xml')->content());
    }

    public function testKeepFilesComparesBothFieldsInOnePlainForm(): void
    {
        $crawler = $this->browser()->visit('/demos/upload/keep-files')->assertSuccessful()->crawler();
        $form = $crawler->filter('form[name="keep_files_form"]');

        self::assertSame('false', $form->attr('data-turbo'));
        self::assertSame(1, $form->filter('input[type="file"][name="keep_files_form[classic]"]')->count());
        self::assertSame(1, $form->filter('.ux-upload input[type="hidden"][name="keep_files_form[upload]"]')->count());
        self::assertSame(2, $crawler->filter('[data-upload-payload-target="size"]')->count());
    }

    public function testKeepFilesRendersTheErrorWith422(): void
    {
        $this->browser()
            ->visit('/demos/upload/keep-files')
            ->click('Save document')
            ->assertStatus(422)
            ->assertSee('Please enter a title.')
        ;
    }

    public function testKeepFilesReportsWhichFileStaysAfterTheError(): void
    {
        $directory = sys_get_temp_dir().'/keep-files-'.bin2hex(random_bytes(4));
        mkdir($directory);
        $pdf = $directory.'/contract.pdf';
        file_put_contents($pdf, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");

        try {
            // Without JavaScript the browser sends both files with the form
            $this->browser()
                ->visit('/demos/upload/keep-files')
                ->attachFile('keep_files_form[classic]', $pdf)
                ->attachFile('keep_files_form[upload]', $pdf)
                ->click('Save document')
                ->assertStatus(422)
                ->assertSeeIn('#keep-files-classic-outcome', 'Empty again.')
                ->assertSeeIn('#keep-files-upload-outcome', 'File kept.')
                ->assertElementAttributeContains('input[type="hidden"][name="keep_files_form[upload]"]', 'value', 'contract.pdf')
            ;
        } finally {
            unlink($pdf);
            rmdir($directory);
        }
    }

    public function testADemoPageNamesTheVisitorBeforeAnyUpload(): void
    {
        // Several files upload in parallel: each request must find the same owner in the session
        $this->browser()
            ->visit('/demos/upload/turbo')
            ->assertSuccessful()
            ->use(static function (KernelBrowser $client): void {
                self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $client->getRequest()->getSession()->get('_ux_upload_owner'));
            })
        ;
    }

    public function testNoUploadRouteServesAFile(): void
    {
        // The package page, the demos and their index: a route added here has to say what it sends back
        $routes = array_keys(self::getContainer()->get('router')->getRouteCollection()->all());
        $routes = array_values(array_filter($routes, static fn (string $name): bool => 'app_upload' === $name || str_starts_with($name, 'app_demo_upload')));
        sort($routes);

        self::assertSame([
            'app_demo_upload',
            'app_demo_upload_dropzone',
            'app_demo_upload_input',
            'app_demo_upload_keep_files',
            'app_demo_upload_live_avatar',
            'app_demo_upload_resumable',
            'app_demo_upload_turbo',
            'app_upload',
        ], $routes);
    }

    #[DataProvider('provideLimitedFields')]
    public function testEveryFieldStopsAtOneMegabyte(string $url, string $form): void
    {
        $help = $this->browser()->visit($url)->assertSuccessful()->crawler()->filter($form.' .ux-upload')->text();

        self::assertStringContainsString('1 M', $help);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLimitedFields(): iterable
    {
        yield 'documents' => ['/demos/upload/dropzone', 'form[name="upload_dropzone_form"]'];
        yield 'photos' => ['/demos/upload/input', 'form[name="upload_enhanced_input_form"]'];
        yield 'avatar' => ['/demos/upload/live-avatar', '.UploadAvatar'];
        yield 'archive' => ['/demos/upload/resumable', 'form[name="upload_large_file_form"]'];
    }

    public function testLiveAvatarIsThePreviewOfTheField(): void
    {
        $crawler = $this->browser()->visit('/demos/upload/live-avatar')->assertSuccessful()->crawler();

        // The avatar is the preview the field draws: the stage adds no script of its own
        $stage = $crawler->filter('.LiveAvatar');
        self::assertNull($stage->attr('data-controller'));
        self::assertNull($stage->attr('data-action'));
        self::assertCount(1, $crawler->filter('.UploadAvatar .ux-upload--previews'));
        self::assertCount(1, $crawler->filter('.UploadAvatar > .UploadAvatar_picture'));
    }

    public function testLiveAvatarTellsTheComponentWhenTheFieldRemovesItsFile(): void
    {
        $field = $this->browser()->visit('/demos/upload/live-avatar')->assertSuccessful()->crawler()->filter('.UploadAvatar [data-live-ignore]');

        self::assertSame('symfony--ux-upload--upload:remove->live#action', $field->attr('data-action'));
        self::assertSame('clearUpload', $field->attr('data-live-action-param'));
        self::assertSame('photo', $field->attr('data-live-property-param'));
    }

    public function testTurboDemoRendersErrorsInTheFrame(): void
    {
        $this->browser()
            ->visit('/demos/upload/turbo')
            ->assertSeeElement('turbo-frame#upload-gallery-form form[name="upload_gallery_form"]')
            ->assertSeeElement('#upload-albums')
            ->click('Save album')
            ->assertStatus(422)
            ->assertSeeIn('turbo-frame#upload-gallery-form', 'Please name the album.')
        ;
    }

    public function testSimpleInputDropsTheItemChrome(): void
    {
        $crawler = $this->browser()->visit('/demos/upload/input')->assertSuccessful()->crawler();
        $item = '[data-symfony--ux-upload--upload-target="template"]';
        $simple = $crawler->filter('form[name="upload_simple_input_form"] template'.$item)->html();
        $enhanced = $crawler->filter('form[name="upload_enhanced_input_form"] template'.$item)->html();

        self::assertStringNotContainsString('ux-upload__progress', $simple);
        self::assertStringNotContainsString('ux-upload__actions', $simple);
        self::assertStringContainsString('ux-upload__progress', $enhanced);
        self::assertStringContainsString('ux-upload__actions', $enhanced);
    }

    public function testCardGridUsesDropzonePreviews(): void
    {
        $crawler = $this->browser()->visit('/demos/upload/dropzone')->assertSuccessful()->crawler();

        self::assertSame(2, $crawler->filter('.ux-upload--dropzone input[type="file"][multiple]')->count());
        self::assertSame(1, $crawler->filter('form[name="upload_card_grid_form"] .ux-upload--previews')->count());
        self::assertStringContainsString('--ux-upload-radius: 1rem', $crawler->filter('style')->text(''));
    }

    public function testResumableDemoUsesTheLargeUploader(): void
    {
        $this->browser()
            ->visit('/demos/upload/resumable')
            ->assertSuccessful()
            ->assertSee('Up to 1 MB, sent in 64 KB chunks')
            ->assertSeeElement('[data-upload-samples-kind-value="large"][data-upload-samples-size-value="1048576"]')
            ->assertSeeElement('.Resumable input[type="file"][accept="application/zip"]')
            ->assertElementAttributeContains('[data-upload-samples-kind-value="large"]', 'data-upload-samples-names-value', 'dinosaur-dna-backup.zip')
        ;
    }

    public function testSampleButtonsNameTheFilesTheyBuild(): void
    {
        $names = 'data-upload-samples-names-value';

        $this->browser()
            ->visit('/demos/upload/input')
            ->assertElementAttributeContains('.FieldStyle--simple [data-controller="upload-samples"]', $names, 'dinosaurs-are-alive.pdf')
            ->visit('/demos/upload/dropzone')
            ->assertElementAttributeContains('.FieldStyle--list [data-controller="upload-samples"]', $names, 'world-domination-plan.pdf')
            ->assertElementAttributeContains('.FieldStyle--list [data-controller="upload-samples"]', $names, 'secret-ux-roadmap.pdf')
            ->visit('/demos/upload/live-avatar')
            ->assertElementAttributeContains('[data-controller="upload-samples"]', $names, 'profile-pic-final-v2.jpg')
            ->visit('/demos/upload/keep-files')
            ->assertSee('world-domination-plan.pdf')
        ;
    }

    public function testARefusedRequestIsNeverSlowedDown(): void
    {
        $listeners = array_map(
            static function (callable $listener): string {
                // A listener is an object method, a static method or a function
                $target = \is_array($listener) ? $listener[0] : $listener;

                return \is_object($target) ? $target::class : $target;
            },
            self::getContainer()->get('event_dispatcher')->getListeners(KernelEvents::REQUEST),
        );

        $router = array_search(RouterListener::class, $listeners, true);
        $quota = array_search(UploadDemoQuotaListener::class, $listeners, true);
        $slowDown = array_search(SlowDemoUploadListener::class, $listeners, true);

        self::assertNotFalse($quota);
        self::assertNotFalse($slowDown);
        self::assertLessThan($quota, $router, 'The quota reads the route.');
        self::assertLessThan($slowDown, $quota, 'A slowed-down chunk holds a PHP worker: a refused one must not.');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideDemos(): iterable
    {
        foreach (self::DEMOS as $url => $name) {
            yield $name => [$url, $name];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideDemoUrls(): iterable
    {
        foreach (self::DEMOS as $url => $name) {
            yield $name => [$url];
        }
    }
}
