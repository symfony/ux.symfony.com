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

#[AsTwigComponent('PackageHeader', template: 'components/Package/PackageHeader.html.twig')]
final class PackageHeader
{
    public UxPackage $package;

    public string $eyebrowText = '';

    /**
     * Render the "composer require" command?
     */
    public bool $command = true;

    /**
     * URL of the package live demos, shown as an extra header link.
     */
    public ?string $demosUrl = null;

    public function __construct(private UxPackageRepository $packageRepository)
    {
    }

    public function mount(UxPackage|string $package): void
    {
        $this->package = \is_string($package) ? $this->packageRepository->find($package) : $package;
    }
}
