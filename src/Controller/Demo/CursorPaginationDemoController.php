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

use App\Service\CursorPayloadPreview;
use App\Service\DemoRepository;
use App\Service\UxReleaseFeed;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Pagination\PaginatorInterface;

final class CursorPaginationDemoController extends AbstractController
{
    #[Route('/demos/pagination/cursor-pagination', name: 'app_demo_pagination_cursor')]
    public function __invoke(
        Request $request,
        CursorPayloadPreview $cursorPayloadPreview,
        PaginatorInterface $paginator,
        UxReleaseFeed $releases,
        DemoRepository $demoRepository,
    ): Response {
        $withDeletedItem = $request->query->getBoolean('deleted');
        $pagination = $paginator
            ->cursor($releases->all($withDeletedItem))
            ->orderBy('id', 'DESC')
            ->perPage(5)
            ->context('demo-cursor')
            ->queryParameters(array_filter(['deleted' => $withDeletedItem ? 1 : null]))
            ->paginate();

        $demo = $demoRepository->find('pagination', 'cursor-pagination');

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
            'pagination' => $pagination,
            'cursorPayload' => $cursorPayloadPreview->decode($pagination->getCursor()),
            'nextCursorPayload' => $cursorPayloadPreview->decode($pagination->getNextCursor()),
            'withDeletedItem' => $withDeletedItem,
        ]);
    }
}
