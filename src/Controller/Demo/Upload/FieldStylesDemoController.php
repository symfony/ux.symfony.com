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
use App\Model\LiveDemo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Upload\Upload\UploadedFile;

#[Route('/demos/upload')]
final class FieldStylesDemoController extends AbstractController
{
    /**
     * Each field style demo compares two variants of the same layout, one form each.
     */
    private const FIELD_STYLES = [
        'input' => [
            'name' => 'Simple & Enhanced Input',
            'description' => 'The compact field: a native input, or a list with previews and progress.',
            'longDescription' => 'The compact layout of [`UploadType`](/upload), twice. On the left, the browser file input,
                which uploads in the background. On the right, a list of files with thumbnails, progress,
                and buttons to cancel, retry, or remove each transfer.',
            'tags' => ['upload', 'compact', 'preview'],
            'variants' => [
                'simple' => ['form' => UploadSimpleInputForm::class, 'title' => 'Simple', 'lines' => [26, 29], 'sample' => 'pdf', 'names' => ['dinosaurs-are-alive.pdf']],
                'enhanced' => ['form' => UploadEnhancedInputForm::class, 'title' => 'Enhanced', 'lines' => [26, 32], 'sample' => 'images'],
            ],
        ],
        'dropzone' => [
            'name' => 'Dropzone & Card Grid',
            'description' => 'A large drop target, with a file list or a grid of image cards.',
            'longDescription' => 'The dropzone layout of [`UploadType`](/upload): drop, paste, or click to browse. Without previews, files line up under
                the target. With `show_preview`, each image becomes a card in a responsive grid.',
            'tags' => ['upload', 'dropzone', 'gallery'],
            'variants' => [
                'list' => ['form' => UploadDropzoneForm::class, 'title' => 'File list', 'lines' => [26, 31], 'sample' => 'pdf', 'names' => ['world-domination-plan.pdf', 'secret-ux-roadmap.pdf']],
                'grid' => ['form' => UploadCardGridForm::class, 'title' => 'Card grid', 'lines' => [26, 32], 'sample' => 'images'],
            ],
        ],
    ];

    #[Route('/input', name: 'app_demo_upload_input', defaults: ['style' => 'input'])]
    #[Route('/dropzone', name: 'app_demo_upload_dropzone', defaults: ['style' => 'dropzone'])]
    public function fieldStyle(Request $request, string $style): Response
    {
        $definition = self::FIELD_STYLES[$style];
        $route = 'app_demo_upload_'.$style;

        $forms = [];
        $submitted = false;
        foreach ($definition['variants'] as $key => $variant) {
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

        $demo = new LiveDemo(
            'upload-'.$style,
            name: $definition['name'],
            description: $definition['description'],
            author: 'smnandre',
            publishedAt: '2026-11-15',
            tags: $definition['tags'],
            longDescription: $definition['longDescription'],
            route: $route,
            template: 'demos/upload/field_style.html.twig',
        );

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'style' => $style,
            'variants' => $forms,
        ], new Response(status: $submitted ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
