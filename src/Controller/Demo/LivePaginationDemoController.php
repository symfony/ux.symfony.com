<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Demo;

use App\Service\DemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LivePaginationDemoController extends AbstractController
{
    #[Route('/demos/pagination/live-pagination', name: 'app_demo_pagination_live')]
    public function __invoke(DemoRepository $demoRepository): Response
    {
        $demo = $demoRepository->find('pagination', 'pagination');

        return $this->render($demo->getTemplate(), ['demo' => $demo]);
    }
}
