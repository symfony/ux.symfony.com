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

class LiveComponentController extends AbstractController
{
    #[Route('/live-component', name: 'app_live_component')]
    public function __invoke(UxPackageRepository $packageRepository, DemoRepository $demoRepository): Response
    {
        $package = $packageRepository->find('live-component');

        return $this->render('ux_packages/live_component.html.twig', [
            'package' => $package,
            'demos' => $demoRepository->findByPackage('live-component'),
        ]);
    }

    #[Route('/live-component/demos/{demo}', name: 'app_live_component_demo_redirect')]
    public function redirectDemo(DemoRepository $demoRepository, ?string $demo = null): Response
    {
        if (null === $demo || '' === $demo) {
            return $this->redirectToRoute('app_demos');
        }

        try {
            // Permanent Redirect old URL
            $liveDemo = $demoRepository->find('live-component', $demo);

            return $this->redirectToRoute($liveDemo->getRoute(), [], 301);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException(\sprintf('Live Component demo "%s" not found.', $demo), previous: $e);
        }
    }
}
