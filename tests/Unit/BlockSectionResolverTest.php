<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit;

use App\Service\Toolkit\BlockSectionResolver;
use App\Service\Toolkit\ToolkitService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BlockSectionResolverTest extends KernelTestCase
{
    public function testGroupsVariantsIntoASingleSection(): void
    {
        $resolver = self::getContainer()->get(BlockSectionResolver::class);
        $kit = self::getContainer()->get(ToolkitService::class)->getKit('shadcn');

        $login = $resolver->findSection($kit, 'login');

        self::assertNotNull($login);
        self::assertSame('login', $login->slug);
        self::assertSame('Login', $login->name);
        self::assertSame(
            ['login-01', 'login-02'],
            array_map(static fn ($recipe) => $recipe->name, $login->recipes),
        );
    }

    public function testUnknownSectionReturnsNull(): void
    {
        $resolver = self::getContainer()->get(BlockSectionResolver::class);
        $kit = self::getContainer()->get(ToolkitService::class)->getKit('shadcn');

        self::assertNull($resolver->findSection($kit, 'does-not-exist'));
    }
}
