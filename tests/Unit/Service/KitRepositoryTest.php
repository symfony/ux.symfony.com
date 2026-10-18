<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Service\Toolkit\KitRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitManifest;
use Symfony\UX\Toolkit\Registry\RegistryInterface;

#[CoversClass(KitRepository::class)]
final class KitRepositoryTest extends TestCase
{
    /** @var list<string> */
    private array $builtKits = [];

    public function testKitsAreBuiltOnceAcrossRequestsWithACache(): void
    {
        $cache = new ArrayAdapter();

        $this->createRepository($cache)->all();
        $this->createRepository($cache)->get('shadcn');

        self::assertSame(['flowbite', 'shadcn'], $this->builtKits);
    }

    public function testKitsAreBuiltOnEveryRequestWithoutACache(): void
    {
        $cache = new NullAdapter();

        $this->createRepository($cache)->get('shadcn');
        $this->createRepository($cache)->get('shadcn');

        self::assertSame(['shadcn', 'shadcn'], $this->builtKits);
    }

    public function testAKitIsBuiltOncePerRequestWithoutACache(): void
    {
        $repository = $this->createRepository(new NullAdapter());

        $repository->get('shadcn');
        $repository->all();

        self::assertSame(['shadcn', 'flowbite'], $this->builtKits);
    }

    public function testAllKitsAreKeyedById(): void
    {
        $kits = $this->createRepository(new NullAdapter())->all();

        self::assertSame(['flowbite', 'shadcn'], array_keys($kits));
        self::assertSame('shadcn', $kits['shadcn']->manifest->name);
    }

    public function testAnUnknownKitIsNeverBuilt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kit "unknown" does not exist.');

        try {
            $this->createRepository(new NullAdapter())->get('unknown');
        } finally {
            self::assertSame([], $this->builtKits);
        }
    }

    private function createRepository(CacheInterface $cache): KitRepository
    {
        $registry = new class(function (string $kitName): void {
            $this->builtKits[] = $kitName;
        }) implements RegistryInterface {
            public function __construct(private readonly \Closure $onBuild)
            {
            }

            public static function supports(string $kitName): bool
            {
                return true;
            }

            public function getKit(string $kitName): Kit
            {
                ($this->onBuild)($kitName);

                return new Kit('/kits/'.$kitName, new KitManifest($kitName, 'A kit', 'MIT', 'https://example.com'));
            }
        };

        return new KitRepository($registry, $cache, static fn () => ['flowbite', 'shadcn']);
    }
}
