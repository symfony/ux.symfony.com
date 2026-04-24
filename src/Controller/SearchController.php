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

use App\Service\Search\SearchClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function __invoke(
        SearchClient $searchClient,
        #[MapQueryParameter] ?string $q = null,
    ): Response {
        $q = trim($q ?? '');

        $groups = ['package' => [], 'demo' => [], 'recipe' => []];

        if ('' !== $q) {
            $result = $searchClient->admin()
                ->index(SearchClient::INDEX)
                ->search($q, [
                    'limit' => 50,
                    'attributesToHighlight' => ['humanName', 'name', 'description'],
                ]);

            foreach ($result->getHits() as $hit) {
                $type = $hit['type'] ?? null;
                if (isset($groups[$type])) {
                    $groups[$type][] = $hit;
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'q' => $q,
            'groups' => $groups,
            'totals' => [
                'package' => \count($groups['package']),
                'demo' => \count($groups['demo']),
                'recipe' => \count($groups['recipe']),
            ],
            'total' => \count($groups['package']) + \count($groups['demo']) + \count($groups['recipe']),
        ]);
    }
}
