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

use App\Service\DemoRepository;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaginationController extends AbstractController
{
    #[Route('/pagination', name: 'app_pagination')]
    public function __invoke(UxPackageRepository $packageRepository, DemoRepository $demoRepository): Response
    {
        return $this->render('ux_packages/pagination.html.twig', [
            'package' => $packageRepository->find('pagination'),
            'demos' => $demoRepository->findByPackage('pagination'),
        ]);
    }
}
