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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{
    private const DEMO_IMAGES = [
        '/uploads/col-de-saint-bonnet.jpg',
        '/uploads/gros-caillou.jpg',
    ];

    #[Route('/image', name: 'app_image')]
    public function __invoke(
        UxPackageRepository $packageRepository,
        #[Autowire('%kernel.project_dir%/public')] string $publicDir,
    ): Response {
        $package = $packageRepository->find('image');

        $originalSizes = [];
        foreach (self::DEMO_IMAGES as $path) {
            $originalSizes[$path] = filesize($publicDir.$path);
        }

        return $this->render('ux_packages/image.html.twig', [
            'package' => $package,
            'original_sizes' => $originalSizes,
        ]);
    }
}
