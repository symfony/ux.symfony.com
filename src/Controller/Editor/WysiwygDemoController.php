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

use App\Entity\Editor\DemoArticle;
use App\Form\Editor\DemoArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WysiwygDemoController extends AbstractController
{
    #[Route('/editor/wysiwyg', name: 'demo_editor_wysiwyg', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $article = new DemoArticle();
        $form = $this->createForm(DemoArticleType::class, $article);
        $form->handleRequest($request);

        $entityDump = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $entityDump = json_encode([
                'title' => $article->title,
                'body'  => $article->body?->html,
                'meta'  => $article->body?->getMetadata(),
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);
        }

        return $this->render('editor/wysiwyg.html.twig', ['form' => $form, 'entityDump' => $entityDump]);
    }
}
