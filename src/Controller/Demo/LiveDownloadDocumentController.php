<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Demo;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LiveDownloadDocumentController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/assets/documents/live-components.md')]
        private readonly string $documentPath,
    ) {
    }

    #[Route(
        '/demos/live-component/live-download/live-components.md',
        name: 'app_demo_live_component_live_download_document',
        methods: ['GET'],
    )]
    public function __invoke(): BinaryFileResponse
    {
        return $this->file($this->documentPath, 'live-components.md');
    }
}
