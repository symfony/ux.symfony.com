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

use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * A family of block variants sharing the same base name (e.g. login-01 and login-02 -> "Login").
 */
final class BlockSection
{
    /**
     * @param non-empty-list<Recipe> $recipes
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly array $recipes,
    ) {
    }
}
