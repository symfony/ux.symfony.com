<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Demo\Upload;

use App\EventListener\SlowDemoUploadListener;
use App\Form\UploadLargeFileForm;
use App\Model\LiveDemo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResumableUploadDemoController extends AbstractController
{
    #[Route('/demos/upload/resumable', name: 'app_demo_upload_resumable')]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(UploadLargeFileForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $this->addFlash('resumable_success', \sprintf(
                '%s received (%d MB). Destroyed on arrival: demo server.',
                $file?->getClientOriginalName() ?? 'No file',
                ($file?->getSize() ?? 0) / 1024 / 1024,
            ));

            return $this->redirectToRoute('app_demo_upload_resumable');
        }

        $demo = new LiveDemo(
            'upload-resumable',
            name: 'Resumable Large Files',
            description: 'Pause a transfer, reload the page, and pick up where it stopped.',
            author: 'smnandre',
            publishedAt: '2026-11-15',
            tags: ['upload', 'chunks', 'resume'],
            longDescription: 'With [`UploadType`](/upload), large files travel in chunks, two at a time. Pause the transfer, or reload the
                page halfway: selecting the same file again sends only the missing chunks.',
            route: 'app_demo_upload_resumable',
            template: 'demos/upload/resumable.html.twig',
        );

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'form' => $form,
            'sample_name' => SlowDemoUploadListener::FILENAME,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
