<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Form\UploadCardGridForm;
use App\Form\UploadDocumentForm;
use App\Service\DemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadController extends AbstractController
{
    #[Route('/upload', name: 'app_upload')]
    public function __invoke(UxPackageRepository $packageRepository, DemoRepository $demoRepository, Request $request): Response
    {
        $form = $this->createForm(UploadDocumentForm::class, options: [
            'action' => $this->generateUrl('app_upload', ['_fragment' => 'try']),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // A real application moves the file to its final place here. This public server destroyed it already.
            $this->addFlash('upload_success', 'Document received. Destroyed on arrival: this is a demo server.');

            return $this->redirectToRoute('app_upload', ['_fragment' => 'try']);
        }

        return $this->render('ux_packages/upload.html.twig', [
            'package' => $packageRepository->find('upload'),
            'form' => $form,
            // Shown as a running example: the field uploads on its own, the form is never submitted
            'gallery' => $this->createForm(UploadCardGridForm::class),
            'demos' => $demoRepository->findByPackage('upload'),
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
