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
use Symfony\Component\HttpFoundation\Request;

/**
 * The build that preview URLs carry as `v`: a preview of the current build never changes, so it can be cached
 * for as long as the build lives.
 */
final class PreviewVersion
{
    public const QUERY_PARAMETER = 'v';

    public function __construct(
        // Given to the compiled container at runtime, it changes on every container build (each deploy).
        #[Autowire(expression: "parameter('container.build_id')")]
        public readonly string $id,
    ) {
    }

    public function isRequested(Request $request): bool
    {
        return $this->id === $request->query->get(self::QUERY_PARAMETER);
    }
}
