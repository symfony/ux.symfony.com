<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Toolkit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireCallable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Registry\RegistryInterface;

/**
 * The Toolkit kits, each built once by the registry then read from the cache.
 */
final class KitRepository
{
    /** @var list<string>|null */
    private ?array $kitIds = null;

    /** @var array<string, Kit> */
    private array $kits = [];

    /**
     * @param \Closure(): list<string> $listKitIds
     */
    public function __construct(
        #[Autowire(service: '.ux_toolkit.registry.local')]
        private readonly RegistryInterface $registry,
        #[Autowire(service: 'app.toolkit_kits_cache')]
        private readonly CacheInterface $cache,
        #[AutowireCallable(callable: [LocalRegistry::class, 'getAvailableKitsName'])]
        private readonly \Closure $listKitIds,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getKitIds(): array
    {
        return $this->kitIds ??= $this->cache->get('toolkit_kit_ids', fn () => ($this->listKitIds)());
    }

    public function get(string $kitId): Kit
    {
        if (!\in_array($kitId, $this->getKitIds(), true)) {
            throw new \InvalidArgumentException(\sprintf('Kit "%s" does not exist.', $kitId));
        }

        return $this->kits[$kitId] ??= $this->cache->get('toolkit_kit.'.$kitId, fn () => $this->registry->getKit($kitId));
    }

    /**
     * @return array<string, Kit>
     */
    public function all(): array
    {
        $kits = [];
        foreach ($this->getKitIds() as $kitId) {
            $kits[$kitId] = $this->get($kitId);
        }

        return $kits;
    }
}
