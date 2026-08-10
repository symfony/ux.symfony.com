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

use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeType;

/**
 * Groups a kit's block recipes into sections, deriving the section from the recipe slug by dropping
 * the trailing "-NN" variant suffix (login-01, login-02 -> "login"). Blocks with no numeric suffix
 * are their own section.
 */
final class BlockSectionResolver
{
    /**
     * @return list<BlockSection>
     */
    public function forKit(Kit $kit): array
    {
        $recipesBySlug = [];
        foreach ($kit->getRecipes(RecipeType::Block) as $recipe) {
            $recipesBySlug[$this->sectionSlug($recipe->name)][] = $recipe;
        }
        ksort($recipesBySlug);

        $sections = [];
        foreach ($recipesBySlug as $slug => $recipes) {
            usort($recipes, static fn (Recipe $a, Recipe $b): int => strcmp($a->name, $b->name));
            $sections[] = new BlockSection($slug, $this->humanize($slug), $recipes);
        }

        return $sections;
    }

    public function findSection(Kit $kit, string $slug): ?BlockSection
    {
        foreach ($this->forKit($kit) as $section) {
            if ($section->slug === $slug) {
                return $section;
            }
        }

        return null;
    }

    private function sectionSlug(string $recipeName): string
    {
        return preg_replace('/-\d+$/', '', $recipeName);
    }

    private function humanize(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }
}
