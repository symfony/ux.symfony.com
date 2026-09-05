<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Components;

use App\Twig\Components\DownloadFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\LiveResponder;

final class DownloadFilesTest extends TestCase
{
    public function testDefinesThreeDownloadExamples()
    {
        $component = $this->createComponent();
        $examples = $component->getExamples();

        self::assertSame(['url', 'file', 'events'], array_keys($examples));
        self::assertSame('downloadUrl', $examples['url']['action']);
        self::assertSame('downloadGeneratedFile', $examples['file']['action']);
        self::assertSame('downloadFileWithEvents', $examples['events']['action']);
    }

    public function testGeneratedFileDownloadUsesCurrentState()
    {
        $component = $this->createComponent();
        $component->totalDownloads = 3;

        $response = $component->downloadGeneratedFile();

        $content = $response->content;
        self::assertInstanceOf(\Closure::class, $content);
        self::assertSame([
            "Symfony UX LiveComponent report\n",
            "Report number: 1\n",
            "Total downloads: 4\n",
        ], iterator_to_array($content()));
        self::assertSame('live-report-1.txt', $response->filename);
        self::assertSame('text/plain; charset=UTF-8', $response->contentType);
        self::assertNull($response->size);
        self::assertSame(1, $component->fileDownloads);
        self::assertSame(4, $component->totalDownloads);
        self::assertSame('live-report-1.txt', $component->lastGeneratedFilename);
    }

    public function testUrlDownloadUpdatesProps()
    {
        $component = $this->createComponent();
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_demo_live_component_live_download_document')
            ->willReturn('/demos/live-component/live-download/live-components.md');

        $response = $component->downloadUrl($urlGenerator);

        self::assertTrue($response->isDownloadUrl());
        self::assertSame('/demos/live-component/live-download/live-components.md', $response->url);
        self::assertSame(1, $component->urlDownloads);
        self::assertSame(1, $component->totalDownloads);
    }

    public function testFileDownloadWithEventsQueuesBothEventTypes()
    {
        $responder = new LiveResponder();
        $component = $this->createComponent($responder);

        $response = $component->downloadFileWithEvents();

        self::assertInstanceOf(\SplFileInfo::class, $response->content);
        self::assertSame('live-components.md', $response->filename);
        self::assertSame('text/markdown; charset=UTF-8', $response->contentType);
        self::assertSame(1, $component->totalDownloads);
        self::assertSame([
            [
                'event' => DownloadFiles::DOWNLOAD,
                'data' => ['filename' => 'live-components.md'],
                'target' => null,
                'componentName' => null,
            ],
        ], $responder->getEventsToEmit());
        self::assertSame([
            [
                'event' => DownloadFiles::DOWNLOAD,
                'payload' => ['filename' => 'live-components.md'],
            ],
        ], $responder->getBrowserEventsToDispatch());
    }

    private function createComponent(?LiveResponder $responder = null): DownloadFiles
    {
        $component = new DownloadFiles(__DIR__.'/../../../assets/documents/live-components.md');
        $component->setLiveResponder($responder ?? new LiveResponder());

        return $component;
    }
}
