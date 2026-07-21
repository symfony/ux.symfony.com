<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DependencyInjection\Compiler;

use App\Service\Toolkit\ToolkitControllersImportMapConfigReader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Feeds the decorated config reader's own importmap.php path to the decorator, so a custom
 * `framework.asset_mapper.importmap_path` stays the single source of truth instead of being
 * hardcoded.
 */
final class ToolkitImportMapConfigReaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ToolkitControllersImportMapConfigReader::class)
            || !$container->hasDefinition('asset_mapper.importmap.config_reader')
        ) {
            return;
        }

        $container->getDefinition(ToolkitControllersImportMapConfigReader::class)
            ->setArgument('$importMapConfigPath', $container->getDefinition('asset_mapper.importmap.config_reader')->getArgument(0));
    }
}
