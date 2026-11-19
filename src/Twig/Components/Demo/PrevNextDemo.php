<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Demo;

use App\Model\Demo;
use App\Service\DemoRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Demo:PrevNext')]
final class PrevNextDemo
{
    public function __construct(
        private readonly DemoRepository $demoRepository,
    ) {
    }

    public Demo $demo;
    public bool $loop = true;
    public ?Demo $relatedLiveDemo = null;

    public function getPrevious(bool $loop = false): ?Demo
    {
        return $this->demoRepository->getPrevious($this->demo, $loop);
    }

    public function getNext(bool $loop = false): ?Demo
    {
        return $this->demoRepository->getNext($this->demo, $loop);
    }
}
