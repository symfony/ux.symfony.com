<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class ChangelogTest extends KernelTestCase
{
    use HasBrowser;

    public function testChangelogIsAccessible()
    {
        $this->browser()
            ->visit('/changelog')
            ->assertSuccessful()
            ->assertHtml()
            ->assertSeeIn('h1', 'Changelog')
        ;
    }
}
