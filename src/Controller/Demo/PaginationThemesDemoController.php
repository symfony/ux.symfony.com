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
use Symfony\UX\Pagination\PaginatorInterface;

final class PaginationThemesDemoController extends AbstractController
{
    #[Route('/demos/pagination/themes', name: 'app_demo_pagination_themes')]
    public function __invoke(PaginatorInterface $paginator, DemoRepository $demoRepository): Response
    {
        $pagination = $paginator
            ->query(range(1, 48))
            ->perPage(6)
            ->sliding(3)
            ->paginate()
        ;

        $demo = $demoRepository->find('pagination', 'pagination-themes');

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'pagination' => $pagination,
        ]);
    }
}
