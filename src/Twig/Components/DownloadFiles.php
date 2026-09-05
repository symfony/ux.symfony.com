<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponse;

#[AsLiveComponent]
final class DownloadFiles
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public const DOWNLOAD = 'download';

    private const EXAMPLES = [
        'url' => [
            'title' => 'Download from a URL',
            'description' => 'Send the browser to a dedicated download route.',
            'buttonLabel' => 'Download via URL',
            'icon' => 'lucide:link',
            'action' => 'downloadUrl',
        ],
        'file' => [
            'title' => 'Generate a file',
            'description' => 'Build and send a text report from the current counters.',
            'buttonLabel' => 'Generate text file',
            'icon' => 'lucide:file-down',
            'action' => 'downloadGeneratedFile',
        ],
        'events' => [
            'title' => 'Download with events',
            'description' => 'Emit a LiveComponent event, dispatch a browser event, then send the file.',
            'buttonLabel' => 'Download with events',
            'icon' => 'lucide:radio',
            'action' => 'downloadFileWithEvents',
        ],
    ];

    #[LiveProp]
    public int $urlDownloads = 0;

    #[LiveProp]
    public int $fileDownloads = 0;

    #[LiveProp]
    public int $totalDownloads = 0;

    #[LiveProp]
    public ?string $lastGeneratedFilename = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/assets/documents/live-components.md')]
        private readonly string $downloadDocumentPath,
    ) {
    }

    /**
     * @return array<string, array{title: string, description: string, buttonLabel: string, icon: string, action: string}>
     */
    public function getExamples(): array
    {
        return self::EXAMPLES;
    }

    #[LiveAction]
    public function downloadUrl(
        UrlGeneratorInterface $urlGenerator,
    ): LiveResponse {
        ++$this->urlDownloads;
        ++$this->totalDownloads;

        $downloadUrl = $urlGenerator->generate(
            'app_demo_live_component_live_download_document',
        );

        return LiveResponse::downloadUrl($downloadUrl);
    }

    #[LiveAction]
    public function downloadGeneratedFile(): LiveResponse
    {
        ++$this->fileDownloads;
        ++$this->totalDownloads;

        $number = $this->fileDownloads;
        $total = $this->totalDownloads;
        $filename = "live-report-{$number}.txt";
        $this->lastGeneratedFilename = $filename;

        $lines = [
            "Symfony UX LiveComponent report\n",
            "Report number: {$number}\n",
            "Total downloads: {$total}\n",
        ];

        return LiveResponse::downloadFile(
            static function () use ($lines): iterable {
                foreach ($lines as $line) {
                    yield $line;
                }
            },
            filename: $filename,
            contentType: 'text/plain; charset=UTF-8',
        );
    }

    #[LiveAction]
    public function downloadFileWithEvents(): LiveResponse
    {
        ++$this->totalDownloads;

        $eventData = [
            'filename' => 'live-components.md',
        ];

        $this->emit(
            self::DOWNLOAD,
            $eventData,
        );
        $this->dispatchBrowserEvent(
            self::DOWNLOAD,
            $eventData,
        );

        $file = new \SplFileInfo($this->downloadDocumentPath);

        return LiveResponse::downloadFile(
            $file,
            contentType: 'text/markdown; charset=UTF-8',
        );
    }
}
