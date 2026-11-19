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

use App\Service\DemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UploadDemosController extends AbstractController
{
    #[Route('/demos/upload', name: 'app_demo_upload')]
    public function __invoke(DemoRepository $demoRepository): Response
    {
        return $this->render('demos/upload/index.html.twig', [
            'demos' => $demoRepository->findByPackage('upload'),
        ]);
    }
}
