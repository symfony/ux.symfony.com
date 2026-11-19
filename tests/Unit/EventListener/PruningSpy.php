<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\EventListener;

use Symfony\UX\Upload\Storage\PrunableStorageInterface;

/**
 * @internal
 */
final class PruningSpy implements PrunableStorageInterface
{
    public int $calls = 0;

    /** @var list<int> */
    public array $ages = [];

    public function __construct(private readonly ?\Closure $onPrune = null)
    {
    }

    public function prune(int $maxAge): void
    {
        ++$this->calls;
        $this->ages[] = $maxAge;
        $this->onPrune?->__invoke();
    }
}
