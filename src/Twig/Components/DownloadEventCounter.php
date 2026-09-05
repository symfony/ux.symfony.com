<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DownloadEventCounter
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $count = 0;

    #[LiveProp]
    public ?string $lastFilename = null;

    #[LiveListener(DownloadFiles::DOWNLOAD)]
    public function onDownload(#[LiveArg] string $filename): void
    {
        ++$this->count;
        $this->lastFilename = $filename;
    }
}
