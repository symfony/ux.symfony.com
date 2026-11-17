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

use App\Form\UploadGalleryForm;
use App\Model\LiveDemo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\UX\Upload\Upload\UploadedFile;

final class TurboUploadDemoController extends AbstractController
{
    #[Route('/demos/upload/turbo', name: 'app_demo_upload_turbo')]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(UploadGalleryForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var list<UploadedFile> $photos */
            $photos = $form->get('photos')->getData() ?? [];
            $album = $form->get('album')->getData();
            // A real application moves the photos to their final place here. This public server
            // destroyed them already: the page only needs their names and sizes.

            // Without JavaScript, Turbo is not there: fall back to a redirect.
            if (TurboBundle::STREAM_FORMAT !== $request->getPreferredFormat()) {
                $this->addFlash('turbo_upload_success', \sprintf('Album "%s" received. Photos destroyed on arrival: demo server.', $album));

                return $this->redirectToRoute('app_demo_upload_turbo');
            }

            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('demos/upload/turbo/success.stream.html.twig', [
                'album' => $album,
                'photos' => $photos,
                'form' => $this->createForm(UploadGalleryForm::class),
            ]);
        }

        $demo = new LiveDemo(
            'upload-turbo',
            name: 'Upload with Turbo',
            description: 'Save an album from a Turbo Frame. A Turbo Stream adds it to the page.',
            author: 'smnandre',
            publishedAt: '2026-11-15',
            tags: ['upload', 'Turbo', 'gallery'],
            longDescription: 'An [`UploadType`](/upload) form lives in a Turbo Frame. A validation error swaps only the frame, and the
                photos stay. A valid album comes back as a Turbo Stream: added to the list, form reset,
                no page reload.',
            route: 'app_demo_upload_turbo',
            template: 'demos/upload/turbo.html.twig',
        );

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'form' => $form,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
