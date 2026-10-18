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

class LinkCaptureTest extends KernelTestCase
{
    use HasBrowser;

    public function testRendersTheRequestOfAnIframeNavigation(): void
    {
        $this->browser()
            ->post('/link/posts/42/delete', [
                'headers' => ['Sec-Fetch-Dest' => 'iframe', 'Sec-Fetch-Site' => 'same-origin'],
                'body' => ['_csrf_token' => 'stub.delete-post'],
            ])
            ->assertSuccessful()
            ->assertSee('/posts/42/delete')
            ->assertSee('stub.delete-post');
    }

    public function testAcknowledgesAFetchRequest(): void
    {
        $this->browser()
            ->post('/link/tasks/1/position', [
                'headers' => ['Sec-Fetch-Dest' => 'empty', 'Sec-Fetch-Site' => 'same-origin'],
                'body' => ['position' => '3'],
            ])
            ->assertStatus(204);
    }

    public function testIgnoresADirectVisit(): void
    {
        $this->browser()
            ->visit('/link/posts/42/delete')
            ->assertStatus(404);
    }

    public function testIgnoresACrossSiteRequest(): void
    {
        $this->browser()
            ->post('/link/tasks/1/position', [
                'headers' => ['Sec-Fetch-Dest' => 'empty', 'Sec-Fetch-Site' => 'cross-site'],
            ])
            ->assertStatus(404);
    }
}
