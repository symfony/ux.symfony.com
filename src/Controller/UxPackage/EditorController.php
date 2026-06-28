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

class EditorController extends AbstractController
{
    #[Route('/editor', name: 'app_editor')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('editor');

        return $this->render('ux_packages/editor.html.twig', [
            'package' => $package,
        ]);
    }
}
