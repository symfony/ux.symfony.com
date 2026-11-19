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

use App\Form\KeepFilesForm;
use App\Service\DemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KeepFilesDemoController extends AbstractController
{
    #[Route('/demos/upload/keep-files', name: 'app_demo_upload_keep_files')]
    public function __invoke(Request $request, DemoRepository $demoRepository): Response
    {
        $form = $this->createForm(KeepFilesForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // A real application moves the files to their final place here. This public server
            // destroyed the upload already, PHP removes the classic one itself: it only reads both names.
            $this->addFlash('keep_files_success', [
                'classic' => $form->get('classic')->getData()?->getClientOriginalName(),
                'upload' => $form->get('upload')->getData()?->getClientOriginalName(),
            ]);

            return $this->redirectToRoute('app_demo_upload_keep_files');
        }

        $demo = $demoRepository->find('upload', 'keep-files');

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'form' => $form,
            'kept' => $form->isSubmitted() ? $form->get('upload')->getData() : null,
        ], new Response(status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
