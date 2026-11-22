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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadPageTest extends WebTestCase
{
    public function testUploadedDocumentSurvivesAValidationError(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/upload');
        self::assertResponseIsSuccessful();
        $widget = $crawler->filter('.ux-upload')->first();
        $form = $crawler->selectButton('Save document')->form();
        $policy = $widget->attr('data-symfony--ux-upload--upload-policy-token-value');
        self::assertNotNull($policy);

        $path = tempnam(sys_get_temp_dir(), 'ux-upload-test-');
        self::assertNotFalse($path);
        file_put_contents($path, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");

        try {
            $client->request('POST', '/_ux/upload', [
                'uploader' => 'documents',
                'filename' => 'document.pdf',
                'fileSize' => filesize($path),
                'mimeType' => 'application/pdf',
                'policyToken' => $policy,
            ], ['file' => new UploadedFile($path, 'document.pdf', 'application/pdf', test: true)]);
            self::assertResponseIsSuccessful();
            $reference = $client->getResponse()->getContent();
            self::assertNotFalse($reference);
            $payload = json_decode($reference, true, flags: \JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('token', $payload);

            $data = $form->getPhpValues();
            $data['upload_document_form']['document'] = $reference;
            $data['upload_document_form']['title'] = '';
            $crawler = $client->request($form->getMethod(), $form->getUri(), $data);
            self::assertResponseStatusCodeSame(422);
            self::assertStringContainsString('Please enter a title.', $crawler->text());
            $retained = $crawler->filter('input[type="hidden"][name="upload_document_form[document]"]')->attr('value');
            self::assertNotNull($retained);
            self::assertSame($payload['token'], json_decode($retained, true, flags: \JSON_THROW_ON_ERROR)['token']);

            $form = $crawler->selectButton('Save document')->form();
            $data = $form->getPhpValues();
            $data['upload_document_form']['document'] = $retained;
            $data['upload_document_form']['title'] = 'My document';
            $client->request($form->getMethod(), $form->getUri(), $data);
            self::assertResponseRedirects('/upload#try');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testPackagePage(): void
    {
        $crawler = self::createClient()->request('GET', '/upload');
        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('h1', 'File uploads');
        self::assertSelectorTextContains('body', 'composer require symfony/ux-upload');
        self::assertCount(5, $crawler->filter('[aria-labelledby="features"] li'));
        self::assertSame(
            ['try', 'flow', 'compare', 'field', 'large', 'validation', 'production', 'demos'],
            $crawler->filter('article section[id]')->each(static fn (Crawler $section): string => (string) $section->attr('id')),
        );
        self::assertCount(4, $crawler->filter('#flow ol.UploadFlow > li'));

        self::assertStringStartsWith('Symfony UX Upload', $crawler->filter('title')->text());
        self::assertStringEndsWith('/upload', (string) $crawler->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame('Symfony UX Upload', $crawler->filter('meta[property="og:image:alt"]')->attr('content'));
        self::assertLessThanOrEqual(160, mb_strlen((string) $crawler->filter('meta[name="description"]')->attr('content')));
        // Short enough to reach a social card whole
        self::assertStringEndsNotWith('...', (string) $crawler->filter('meta[property="og:description"]')->attr('content'));

        self::assertCount(1, $crawler->filter('.PackageHeader a[href="/demos/upload"]'));
        self::assertCount(6, $crawler->filter('#demos .Card'));
        self::assertCount(1, $crawler->filter('#demos a[href="/demos/upload"]'));
    }

    public function testTheSwapIsShownAsADiffAndCopiedWithoutIt(): void
    {
        $crawler = self::createClient()->request('GET', '/upload');
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('FileType::class', $crawler->filter('#try .hl-deletion')->text());
        self::assertStringContainsString('UploadType::class', $crawler->filter('#try .hl-addition')->text());

        // The copy button gives the code after the swap, without the removed line
        $copied = $crawler->filter('#try [data-clipboarder-source-value]')->first()->attr('data-clipboarder-source-value');
        self::assertStringContainsString("->add('document', UploadType::class, [", $copied);
        self::assertStringNotContainsString('FileType', $copied);
        self::assertStringNotContainsString('{', $copied);
    }

    public function testTheComparisonSaysEveryAnswerAloud(): void
    {
        $crawler = self::createClient()->request('GET', '/upload');
        self::assertResponseIsSuccessful();

        $comparison = $crawler->filter('#compare table.UploadCompare');
        self::assertNotSame('', $comparison->filter('caption')->text());
        self::assertGreaterThan(1, $comparison->filter('tbody th[scope="rowgroup"]')->count());

        // A tick or a cross is a drawing: each cell also says Yes or No
        $cells = $comparison->filter('tbody td');
        self::assertGreaterThan(10, $cells->count());
        foreach ($cells as $cell) {
            $cell = new Crawler($cell);
            self::assertCount(1, $cell->filter('svg[aria-hidden="true"]'));
            self::assertSame('yes' === $cell->attr('data-mark') ? 'Yes' : 'No', $cell->filter('.sr-only')->text());
        }
        self::assertCount(0, $comparison->filter('td[data-column="UploadType"][data-mark="no"]'), 'UploadType does everything the table lists.');
    }

    public function testTheDocumentationLinksPointToTheSymfonyRepository(): void
    {
        $crawler = self::createClient()->request('GET', '/upload');
        self::assertResponseIsSuccessful();

        $links = $crawler->filter('a[href*="/src/Upload/doc/"]')->each(static fn (Crawler $link): string => (string) $link->attr('href'));
        self::assertGreaterThan(10, \count($links));
        foreach ($links as $link) {
            self::assertStringStartsWith('https://github.com/symfony/ux/blob/3.x/src/Upload/doc/', $link);
        }
    }

    public function testTheFieldSectionRunsARealCardGrid(): void
    {
        $crawler = self::createClient()->request('GET', '/upload');

        self::assertCount(1, $crawler->filter('#field .ux-upload--dropzone.ux-upload--previews input[type="file"][multiple]'));
    }
}
