<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Editor;

use App\Entity\Editor\DemoNote;
use App\Form\Editor\DemoNoteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlockDemoController extends AbstractController
{
    #[Route('/editor/block', name: 'demo_editor_block', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $note = new DemoNote();
        $form = $this->createForm(DemoNoteType::class, $note);
        $form->handleRequest($request);

        $entityDump = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $entityDump = json_encode([
                'title'  => $note->title,
                'blocks' => $note->body?->blocks,
                'meta'   => $note->body?->getMetadata(),
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);
        }

        return $this->render('editor/block.html.twig', ['form' => $form, 'entityDump' => $entityDump]);
    }
}
