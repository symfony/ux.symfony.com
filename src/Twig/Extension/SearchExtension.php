<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Extension;

use App\Service\Search\SearchClient;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class SearchExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SearchClient $searchClient,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'meilisearch_url' => $this->searchClient->url(),
            'meilisearch_search_key' => $this->searchClient->publicSearchKey(),
        ];
    }
}
