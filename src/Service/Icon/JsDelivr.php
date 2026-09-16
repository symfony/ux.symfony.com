<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Icon;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Renders icons from the `@iconify-json/*` packages served by jsDelivr, so we
 * don't hit api.iconify.design's rate-limited SVG endpoint.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class JsDelivr
{
    private const TTL = 604800; // 1 week

    public function __construct(
        private HttpClientInterface $http,
        private CacheInterface $cache,
    ) {
    }

    public function svg(string $prefix, string $name): ?string
    {
        return $this->cache->get("icon-svg-{$prefix}-{$name}", function (ItemInterface $item) use ($prefix, $name) {
            $item->expiresAfter(self::TTL);

            return $this->collection($prefix)->svg($name);
        });
    }

    private function collection(string $prefix): IconCollection
    {
        $data = $this->cache->get("icon-collection-{$prefix}", function (ItemInterface $item) use ($prefix) {
            $item->expiresAfter(self::TTL);

            return $this->fetch($prefix);
        });

        return new IconCollection($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(string $prefix): array
    {
        try {
            return $this->http
                ->request('GET', "https://cdn.jsdelivr.net/npm/@iconify-json/{$prefix}/icons.json")
                ->toArray()
            ;
        } catch (ClientExceptionInterface) {
            // an unknown set shouldn't be re-fetched on every miss (transport/server errors still bubble, so they aren't cached)
            return [];
        }
    }
}
