<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Package;

use App\Model\UxPackage;
use App\Service\UxPackageRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('PackageSuggestions', template: 'components/Package/PackageSuggestions.html.twig')]
final class PackageSuggestions
{
    public UxPackage $package;

    public int $limit = 3;

    public function __construct(private UxPackageRepository $packageRepository)
    {
    }

    /**
     * Neighbours of the current package, always the same ones for a given page.
     *
     * @return list<UxPackage>
     */
    public function getPackages(): array
    {
        $packages = array_values(array_filter(
            $this->packageRepository->findAll(removed: false),
            fn (UxPackage $package) => $package->getName() !== $this->package->getName(),
        ));

        $offset = crc32($this->package->getName()) % \count($packages);

        return \array_slice([...\array_slice($packages, $offset), ...\array_slice($packages, 0, $offset)], 0, $this->limit);
    }
}
