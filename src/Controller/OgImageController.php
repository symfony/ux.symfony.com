<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;

class OgImageController extends AbstractController
{
    public function __construct(
        private readonly UxPackageRepository $packageRepository,
        #[Autowire(service: 'profiler')]
        private readonly ?Profiler $profiler = null,
    ) {
    }

    #[Route('/_og/{packageName}', name: 'app_og_image_show', methods: ['GET'], env: 'dev')]
    public function __invoke(string $packageName): Response
    {
        $this->profiler?->disable();

        $response = $this->render('og_image/package.html.twig', [
            'package' => $this->packageRepository->find($packageName),
        ]);

        return $response;
    }
}
