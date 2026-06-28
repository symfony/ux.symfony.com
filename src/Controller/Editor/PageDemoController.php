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

use App\Entity\Editor\DemoLanding;
use App\Form\Editor\DemoLandingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageDemoController extends AbstractController
{
    #[Route('/editor/page', name: 'demo_editor_page', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $landing = new DemoLanding();
        $form = $this->createForm(DemoLandingType::class, $landing);
        $form->handleRequest($request);

        $entityDump = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $entityDump = json_encode([
                'title' => $landing->title,
                'page'  => $landing->homepage ? [
                    'html'       => $landing->homepage->html,
                    'css'        => $landing->homepage->css,
                    'assets'     => $landing->homepage->assets,
                    'components' => $landing->homepage->components,
                ] : null,
                'meta' => $landing->homepage?->getMetadata(),
            ], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR);
        }

        return $this->render('editor/page.html.twig', ['form' => $form, 'entityDump' => $entityDump]);
    }
}
