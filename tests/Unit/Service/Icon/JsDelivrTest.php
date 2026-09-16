<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service\Icon;

use App\Service\Icon\JsDelivr;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class JsDelivrTest extends TestCase
{
    private const COLLECTION = [
        'prefix' => 'lucide',
        'width' => 24,
        'height' => 24,
        'icons' => ['house' => ['body' => '<path d="M0 0"/>']],
        'aliases' => ['home' => ['parent' => 'house']],
    ];

    public function testItRendersAnIconFromTheJsDelivrCollection()
    {
        $requestedUrl = null;
        $http = new MockHttpClient(static function (string $method, string $url) use (&$requestedUrl) {
            $requestedUrl = $url;

            return new MockResponse(json_encode(self::COLLECTION));
        });

        $svg = (new JsDelivr($http, new ArrayAdapter()))->svg('lucide', 'house');

        $this->assertSame('https://cdn.jsdelivr.net/npm/@iconify-json/lucide/icons.json', $requestedUrl);
        $this->assertSame(
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M0 0"/></svg>',
            $svg,
        );
    }

    public function testItFetchesTheCollectionOnlyOnce()
    {
        $jsDelivr = new JsDelivr(
            // a second request would exhaust the factory and throw
            new MockHttpClient([new MockResponse(json_encode(self::COLLECTION))]),
            new ArrayAdapter(),
        );

        $this->assertNotNull($jsDelivr->svg('lucide', 'house'));
        $this->assertNotNull($jsDelivr->svg('lucide', 'home'));
        $this->assertNull($jsDelivr->svg('lucide', 'nope'));
    }

    public function testItCachesTheRenderedSvg()
    {
        $cache = new ArrayAdapter();
        $collection = json_encode(self::COLLECTION);

        $this->assertNotNull((new JsDelivr(new MockHttpClient([new MockResponse($collection)]), $cache))->svg('lucide', 'house'));

        // no responses left: a cache miss would blow up
        $this->assertNotNull((new JsDelivr(new MockHttpClient([]), $cache))->svg('lucide', 'house'));
    }

    public function testAnUnknownCollectionIsNullAndIsNotRefetched()
    {
        $jsDelivr = new JsDelivr(
            new MockHttpClient([new MockResponse('', ['http_code' => 404])]),
            new ArrayAdapter(),
        );

        $this->assertNull($jsDelivr->svg('nope', 'house'));
        $this->assertNull($jsDelivr->svg('nope', 'home'));
    }

    public function testAServerErrorIsNotCached()
    {
        $jsDelivr = new JsDelivr(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 500]),
                new MockResponse(json_encode(self::COLLECTION)),
            ]),
            new ArrayAdapter(),
        );

        try {
            $jsDelivr->svg('lucide', 'house');
            $this->fail('Expected a server exception.');
        } catch (ServerExceptionInterface) {
        }

        $this->assertNotNull($jsDelivr->svg('lucide', 'house'));
    }
}
