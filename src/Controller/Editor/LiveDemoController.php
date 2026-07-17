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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LiveDemoController extends AbstractController
{
    #[Route('/editor/live', name: 'demo_editor_live', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('editor/live.html.twig');
    }
}
