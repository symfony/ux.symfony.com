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

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RouterController extends AbstractController
{
    #[Route('/router', name: 'app_router')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('router');

        return $this->render('ux_packages/router.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/router/demo/blog/{slug}', name: 'demo_blog_post')]
    #[Route('/router/demo/archive/{year}', name: 'demo_blog_archive', requirements: ['year' => '\d{4}'])]
    #[Route(['en' => '/router/demo/about', 'fr' => '/router/demo/a-propos'], name: 'demo_about')]
    public function demo(): Response
    {
        return $this->redirectToRoute('app_router', ['_fragment' => 'demo']);
    }
}
