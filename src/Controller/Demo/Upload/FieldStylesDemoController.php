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

use App\Form\UploadCardGridForm;
use App\Form\UploadDropzoneForm;
use App\Form\UploadEnhancedInputForm;
use App\Form\UploadSimpleInputForm;
use App\Service\DemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Upload\Upload\UploadedFile;

#[Route('/demos/upload')]
final class FieldStylesDemoController extends AbstractController
{
    /**
     * The variants each field style demo compares: two takes on the same layout, one form each.
     */
    private const FIELD_STYLES = [
        'input' => [
            'simple' => ['form' => UploadSimpleInputForm::class, 'title' => 'Simple', 'lines' => [26, 29], 'sample' => 'pdf', 'names' => ['dinosaurs-are-alive.pdf']],
            'enhanced' => ['form' => UploadEnhancedInputForm::class, 'title' => 'Enhanced', 'lines' => [26, 32], 'sample' => 'images'],
        ],
        'dropzone' => [
            'list' => ['form' => UploadDropzoneForm::class, 'title' => 'File list', 'lines' => [26, 31], 'sample' => 'pdf', 'names' => ['world-domination-plan.pdf', 'secret-ux-roadmap.pdf']],
            'grid' => ['form' => UploadCardGridForm::class, 'title' => 'Card grid', 'lines' => [26, 32], 'sample' => 'images'],
        ],
    ];

    #[Route('/input', name: 'app_demo_upload_input', defaults: ['style' => 'input'])]
    #[Route('/dropzone', name: 'app_demo_upload_dropzone', defaults: ['style' => 'dropzone'])]
    public function fieldStyle(Request $request, string $style, DemoRepository $demoRepository): Response
    {
        $demo = $demoRepository->find('upload', $style);
        $route = $demo->getRoute();

        $forms = [];
        $submitted = false;
        foreach (self::FIELD_STYLES[$style] as $key => $variant) {
            $form = $this->createForm($variant['form']);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // Each variant form holds a single upload field: one upload, or a list of them.
                // A real application moves the files to their final place here. This public server
                // destroyed them already: only their references are left.
                $value = current($form->all())->getData();
                $uploads = \is_array($value) ? $value : array_filter([$value]);
                $names = array_map(static fn (UploadedFile $upload): string => $upload->getClientOriginalName(), $uploads);
                $this->addFlash('upload_demo_success', $names
                    ? \sprintf('%s: received %s. Destroyed on arrival: demo server.', $variant['title'], implode(', ', $names))
                    : 'Nothing to receive: select a file first.');

                return $this->redirectToRoute($route);
            }
            $submitted = $submitted || $form->isSubmitted();
            $forms[$key] = $variant + [
                'view' => $form->createView(),
                'file' => 'src/Form/'.(new \ReflectionClass($variant['form']))->getShortName().'.php',
            ];
        }

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'style' => $style,
            'variants' => $forms,
        ], new Response(status: $submitted ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
