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

use App\Enum\PackageGroup;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;

class UxPackagesController extends AbstractController
{
    #[Route('/packages', name: 'app_packages')]
    #[Route('/packages/{group}', name: 'app_packages_group', requirements: ['group' => new EnumRequirement(PackageGroup::class)])]
    public function __invoke(UxPackageRepository $packageRepository, ?PackageGroup $group = null): Response
    {
        return $this->render('main/packages.html.twig', [
            'group' => $group,
            'groups' => PackageGroup::cases(),
            'packages' => $group ? $packageRepository->findByGroup($group) : $packageRepository->findAll(removed: false),
            'removed_packages' => $group ? [] : $packageRepository->findAll(removed: true),
        ]);
    }

    #[Route('/components')]
    public function componentsRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app_packages', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
