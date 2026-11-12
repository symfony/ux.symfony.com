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

use App\Model\LiveDemo;
use App\Model\TurboDemo;
use App\Service\LiveDemoRepository;
use App\Service\TurboDemoRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Demo:PrevNext')]
final class PrevNextDemo
{
    public function __construct(
        private readonly LiveDemoRepository $liveDemoRepository,
        private readonly TurboDemoRepository $turboDemoRepository,
    ) {
    }

    public LiveDemo|TurboDemo $demo;

    public function getPrevious(bool $loop = false): LiveDemo|TurboDemo|null
    {
        return $this->demo instanceof LiveDemo
            ? $this->liveDemoRepository->getPrevious($this->demo->getIdentifier(), $loop)
            : $this->turboDemoRepository->getPrevious($this->demo->getIdentifier(), $loop);
    }

    public function getNext(bool $loop = false): LiveDemo|TurboDemo|null
    {
        return $this->demo instanceof LiveDemo
            ? $this->liveDemoRepository->getNext($this->demo->getIdentifier(), $loop)
            : $this->turboDemoRepository->getNext($this->demo->getIdentifier(), $loop);
    }
}
