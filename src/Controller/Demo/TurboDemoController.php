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

use App\Service\EmojiCollection;
use App\Service\TurboDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/demos/turbo')]
class TurboDemoController extends AbstractController
{
    private const int PER_PAGE = 12;

    public function __construct(
        private readonly TurboDemoRepository $turboDemoRepository,
        private readonly EmojiCollection $emojiCollection,
    ) {
    }

    #[Route('/', name: 'app_demo_turbo')]
    public function __invoke(): Response
    {
        return $this->redirectToRoute('app_demos');
    }

    #[Route('/infinite-scroll', name: 'app_demo_turbo_infinite_scroll')]
    public function infiniteScroll(
        Request $request,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_INT, options: ['min_range' => 1])] int $page = 1,
    ): Response {
        $items = $this->getItems($page);

        $hasMore = \count($this->emojiCollection) > ($page * self::PER_PAGE);
        $nextPage = $hasMore ? $page + 1 : null;

        if ($request->headers->has('Turbo-Frame')) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('demos/turbo/infinite_scroll.html.twig', 'stream_success', [
                'items' => $items,
                'has_more' => $hasMore,
                'next_page' => $nextPage,
            ]);
        }

        return $this->render('demos/turbo/infinite_scroll.html.twig', [
            'demo' => $this->turboDemoRepository->find('infinite-scroll'),
            'items' => $items,
            'has_more' => $hasMore,
            'next_page' => $nextPage,
        ]);
    }

    #[Route('/infinite-scroll-v2.{_format}', name: 'app_demo_turbo_infinite_scroll_2', requirements: ['_format' => 'html|turbo_stream'], format: 'html')]
    public function infiniteScroll2(
        Request $request,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_INT, options: ['min_range' => 1])] int $page = 1,
    ): Response {
        $items = $this->getItems($page);

        $hasMore = \count($this->emojiCollection) > ($page * self::PER_PAGE);
        $nextPage = $hasMore ? $page + 1 : null;

        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('demos/turbo/infinite_scroll_2.html.twig', 'stream_success', [
                'items' => $items,
                'has_more' => $hasMore,
                'next_page' => $nextPage,
            ]);
        }

        return $this->render('demos/turbo/infinite_scroll_2.html.twig', [
            'demo' => $this->turboDemoRepository->find('infinite-scroll-2'),
            'items' => $items,
            'has_more' => $hasMore,
            'next_page' => $nextPage,
        ]);
    }

    /**
     * @return list<array{id: int, emoji: string, color: string}>
     */
    private function getItems(int $page): array
    {
        $emojis = $this->emojiCollection->paginate($page, self::PER_PAGE);
        $colors = $this->getColors();

        $items = [];
        foreach ($emojis as $i => $emoji) {
            $items[] = [
                'id' => $id = ($page - 1) * self::PER_PAGE + $i,
                'emoji' => $emoji,
                'color' => $colors[$id % \count($colors)],
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function getColors(): array
    {
        return [
            '#fbf8cc', '#fde4cf', '#ffcfd2',
            '#f1c0e8', '#cfbaf0', '#a3c4f3',
            '#90dbf4', '#8eecf5', '#98f5e1',
            '#b9fbc0', '#b9fbc0', '#ffc9c9',
            '#d7ffc9', '#c9fffb',
        ];
    }
}
