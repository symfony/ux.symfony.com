<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Search;

use Meilisearch\Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SearchClient
{
    public const INDEX = 'ux_search';

    private ?Client $admin = null;
    private ?string $publicSearchKey = null;

    public function __construct(
        #[Autowire(env: 'APP_MEILISEARCH_URL')] private readonly string $url,
        #[Autowire(env: 'APP_MEILISEARCH_MASTER_KEY')] private readonly string $masterKey,
    ) {
    }

    public function admin(): Client
    {
        return $this->admin ??= new Client($this->url, $this->masterKey);
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * Returns a key scoped to `search` actions on {@see self::INDEX}, safe to expose in the browser.
     * Created on first use and cached by name.
     */
    public function publicSearchKey(): string
    {
        if (null !== $this->publicSearchKey) {
            return $this->publicSearchKey;
        }

        $keyName = 'ux.symfony.com public search key';

        foreach ($this->admin()->getKeys()->getResults() as $key) {
            if ($key->getName() === $keyName) {
                return $this->publicSearchKey = $key->getKey();
            }
        }

        $created = $this->admin()->createKey([
            'name' => $keyName,
            'description' => 'Scoped key for browser-side search',
            'actions' => ['search'],
            'indexes' => [self::INDEX],
            'expiresAt' => null,
        ]);

        return $this->publicSearchKey = $created->getKey();
    }
}
