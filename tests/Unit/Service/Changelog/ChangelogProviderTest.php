<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service\Changelog;

use App\Service\Changelog\ChangelogProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class ChangelogProviderTest extends TestCase
{
    public function testReturnsReleases(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse([
            ['id' => 1, 'name' => 'v3.0.0', 'tag_name' => 'v3.0.0', 'published_at' => '2026-11-01T00:00:00Z', 'body' => 'Notes'],
        ]));

        $changelog = (new ChangelogProvider($httpClient, new ArrayAdapter()))->getChangelog();

        $this->assertSame([['id' => 1, 'name' => 'v3.0.0', 'version' => 'v3.0.0', 'date' => '2026-11-01T00:00:00Z', 'body' => 'Notes']], $changelog);
    }

    public function testReturnsEmptyChangelogWhenGitHubRejectsTheRequest(): void
    {
        $httpClient = new MockHttpClient(new JsonMockResponse(['message' => 'API rate limit exceeded'], ['http_code' => 403]));

        $changelog = (new ChangelogProvider($httpClient, new ArrayAdapter()))->getChangelog();

        $this->assertSame([], $changelog);
    }
}
