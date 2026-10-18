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

use App\Service\Toolkit\PreviewRenderer;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ToolkitController extends AbstractController
{
    #[Route('/toolkit', name: 'app_toolkit')]
    public function index(
        Request $request,
        UxPackageRepository $packageRepository,
        ToolkitService $toolkitService,
        PreviewRenderer $previewRenderer,
        #[MapQueryParameter] ?string $preview = null,
    ): Response {
        if ('demo' === $preview) {
            return $previewRenderer->respondWithTemplate($request, 'shadcn', 'toolkit/_code_block_right.html.twig', $this->generateUrl('app_toolkit', referenceType: UrlGeneratorInterface::ABSOLUTE_URL));
        }

        $package = $packageRepository->find('toolkit');

        return $this->render('ux_packages/toolkit.html.twig', [
            'package' => $package,
            'kits' => $toolkitService->getKits(),
        ]);
    }
}
