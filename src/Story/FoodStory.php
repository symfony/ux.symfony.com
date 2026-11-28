<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Story;

use App\Factory\FoodFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

/**
 * Foods to vote on in the FoodVote live component demo.
 */
#[AsFixture(name: 'food')]
final class FoodStory extends Story
{
    private const FOODS = [
        'Banana 🍌',
        'Apple 🍎',
        'Hamburger 🍔',
        'Watermelon 🍉',
        'Cheese 🧀',
        'Pizza 🍕',
        'Pretzel 🥨',
        'Donut 🍩',
        'Pineapple 🍍',
        'Popcorn 🍿',
        'Egg 🍳',
        'Taco 🌮',
        'Ice Cream 🍦',
        'Cookie 🍪',
    ];

    public function build(): void
    {
        FoodFactory::createSequence(
            array_map(static fn (string $name): array => ['name' => $name], self::FOODS),
        );
    }
}
