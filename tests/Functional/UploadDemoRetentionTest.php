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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * The demo server is public: it keeps a visitor's file no longer than the demo needs it.
 */
final class UploadDemoRetentionTest extends KernelTestCase
{
    use HasBrowser;

    private const SAMPLES = [
        'pdf' => ['document.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n"],
        // A 1x1 PNG
        'png' => ['photo.png', 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='],
        // An empty archive
        'zip' => ['archive.zip', "PK\x05\x06\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"],
    ];

    /**
     * @param array<string, string> $fields
     */
    #[DataProvider('provideForms')]
    public function testAReceivedFileIsDeletedAtOnce(string $url, string $field, string $kind, string $submit, string $receipt, array $fields = []): void
    {
        [$name, $content] = self::SAMPLES[$kind];
        $directory = sys_get_temp_dir().'/upload-retention-'.bin2hex(random_bytes(4));
        mkdir($directory);
        $file = $directory.'/'.$name;
        file_put_contents($file, 'png' === $kind ? base64_decode($content) : $content);
        $before = self::storedFiles();

        try {
            // Without JavaScript the browser sends the file with the form
            $browser = $this->browser()->visit($url)->attachFile($field, $file);
            foreach ($fields as $fieldName => $value) {
                $browser->fillField($fieldName, $value);
            }
            $browser->click($submit)->assertSuccessful()->assertSee($receipt);
        } finally {
            unlink($file);
            rmdir($directory);
        }

        self::assertSame([], array_values(array_diff(self::storedFiles(), $before)), 'A received file stays on the demo server.');
    }

    public function testAFileIsDestroyedTheMomentItsUploadCompletes(): void
    {
        $this->browser()
            ->visit('/demos/upload/keep-files')
            ->use(static function (KernelBrowser $client): void {
                $crawler = $client->getCrawler();
                $policy = $crawler->filter('.ux-upload')->attr('data-symfony--ux-upload--upload-policy-token-value');
                $form = $crawler->selectButton('Save document')->form();
                $path = sys_get_temp_dir().'/upload-retention-'.bin2hex(random_bytes(4)).'.pdf';
                file_put_contents($path, self::SAMPLES['pdf'][1]);

                try {
                    // What the field does with JavaScript: the file travels before the form
                    $client->request('POST', '/_ux/upload', [
                        'uploader' => 'documents',
                        'filename' => 'document.pdf',
                        'fileSize' => filesize($path),
                        'mimeType' => 'application/pdf',
                        'policyToken' => $policy,
                    ], ['file' => new UploadedFile($path, 'document.pdf', 'application/pdf', test: true)]);
                } finally {
                    unlink($path);
                }
                self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
                $reference = (string) $client->getResponse()->getContent();
                $token = json_decode($reference, true, flags: \JSON_THROW_ON_ERROR)['token'];
                parse_str($token, $payload);
                self::assertFileDoesNotExist(self::getContainer()->getParameter('app.upload_demo_dir').'/'.$payload['p']);

                // The demo goes on with the signed reference alone
                $data = $form->getPhpValues();
                $data['keep_files_form']['upload'] = $reference;
                $crawler = $client->request('POST', $form->getUri(), $data);
                self::assertSame(422, $client->getResponse()->getStatusCode());
                self::assertStringContainsString('File kept.', $crawler->filter('#keep-files-upload-outcome')->text());

                // The browser follows the redirect of a saved form
                $data['keep_files_form']['title'] = 'Plan';
                $crawler = $client->request('POST', $form->getUri(), $data);
                self::assertStringContainsString('Received document.pdf', $crawler->filter('#keep-files-upload-outcome')->text());

                // The Remove button still gets its answer
                $client->request('DELETE', '/_ux/upload/remove', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['token' => $token, 'policyToken' => $policy], \JSON_THROW_ON_ERROR));
                self::assertSame(200, $client->getResponse()->getStatusCode(), (string) $client->getResponse()->getContent());
            })
        ;
    }

    public function testAFileSentWithAnInvalidFormIsDestroyedToo(): void
    {
        $directory = sys_get_temp_dir().'/upload-retention-'.bin2hex(random_bytes(4));
        mkdir($directory);
        $file = $directory.'/document.pdf';
        file_put_contents($file, self::SAMPLES['pdf'][1]);
        $before = self::storedFiles();

        try {
            $this->browser()
                ->visit('/demos/upload/keep-files')
                ->attachFile('keep_files_form[upload]', $file)
                ->click('Save document')
                ->assertStatus(422)
                ->assertSeeIn('#keep-files-upload-outcome', 'File kept.')
            ;
        } finally {
            unlink($file);
            rmdir($directory);
        }

        self::assertSame([], array_values(array_diff(self::storedFiles(), $before)), 'The reference is kept, not the file.');
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5?: array<string, string>}>
     */
    public static function provideForms(): iterable
    {
        yield 'Keep Files' => ['/demos/upload/keep-files', 'keep_files_form[upload]', 'pdf', 'Save document', 'Received document.pdf', ['keep_files_form[title]' => 'Plan']];
        yield 'Simple input' => ['/demos/upload/input', 'upload_simple_input_form[document]', 'pdf', 'form[name="upload_simple_input_form"] button[type="submit"]', 'received document.pdf'];
        yield 'Enhanced input' => ['/demos/upload/input', 'upload_enhanced_input_form[photos][]', 'png', 'form[name="upload_enhanced_input_form"] button[type="submit"]', 'received photo.png'];
        yield 'Dropzone list' => ['/demos/upload/dropzone', 'upload_dropzone_form[documents][]', 'pdf', 'form[name="upload_dropzone_form"] button[type="submit"]', 'received document.pdf'];
        yield 'Card grid' => ['/demos/upload/dropzone', 'upload_card_grid_form[photos][]', 'png', 'form[name="upload_card_grid_form"] button[type="submit"]', 'received photo.png'];
        yield 'Resumable' => ['/demos/upload/resumable', 'upload_large_file_form[file]', 'zip', 'Save', 'archive.zip received'];
        yield 'Turbo' => ['/demos/upload/turbo', 'upload_gallery_form[photos][]', 'png', 'Save album', 'Album "Lisbon" received', ['upload_gallery_form[album]' => 'Lisbon']];
        yield 'Package page' => ['/upload', 'upload_document_form[document]', 'pdf', 'Save document', 'Document received', ['upload_document_form[title]' => 'Plan']];
    }

    /**
     * @return list<string>
     */
    private static function storedFiles(): array
    {
        $directory = self::getContainer()->getParameter('app.upload_demo_dir');
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }
}
