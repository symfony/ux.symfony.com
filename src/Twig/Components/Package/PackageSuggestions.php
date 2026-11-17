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
     * @return list<UxPackage>
     */
    public function getPackages(): array
    {
        return \array_slice($this->packageRepository->findRelated($this->package), 0, $this->limit);
    }
}
