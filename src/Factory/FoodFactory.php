<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory;

use App\Entity\Food;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Food>
 */
final class FoodFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Food::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => ucfirst(self::faker()->word()),
            'votes' => self::faker()->numberBetween(0, 100),
        ];
    }
}
