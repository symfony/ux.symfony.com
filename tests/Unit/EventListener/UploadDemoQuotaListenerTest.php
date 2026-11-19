<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\EventListener;

use App\EventListener\UploadDemoQuotaListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class UploadDemoQuotaListenerTest extends TestCase
{
    private const VISITOR = '203.0.113.7';

    private string $directory;
    private string $chunk;
    private PruningSpy $storage;
    private UploadDemoQuotaListener $listener;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/upload-quota-'.bin2hex(random_bytes(4));
        mkdir($this->directory.'/tmp/session', recursive: true);
        $this->chunk = $this->directory.'/tmp/session/chunk';
        $this->storage = new PruningSpy();
        $this->listener = $this->listener($this->storage);
    }

    protected function tearDown(): void
    {
        @unlink($this->chunk);
        rmdir($this->directory.'/tmp/session');
        rmdir($this->directory.'/tmp');
        rmdir($this->directory);
    }

    public function testAVisitorSendsUpToTheAllowance(): void
    {
        self::assertNull($this->send('ux_upload_direct', 'POST', 600));
        self::assertNull($this->send('ux_upload_handle', 'PUT', 400));
    }

    public function testAVisitorWhoSendsTooMuchIsBlocked(): void
    {
        $this->send('ux_upload_direct', 'POST', 900);

        $refusal = $this->send('ux_upload_handle', 'PUT', 200);

        self::assertSame(429, $refusal?->getStatusCode());
        self::assertSame('900', $refusal->headers->get('Retry-After'));
        self::assertSame('application/json', $refusal->headers->get('Content-Type'));
        self::assertStringContainsString('Too many uploads from your network', (string) $refusal->getContent());
    }

    public function testABlockedVisitorStaysBlockedEvenForASmallFile(): void
    {
        $this->send('ux_upload_direct', 'POST', 900);
        $this->send('ux_upload_direct', 'POST', 900);

        self::assertSame(429, $this->send('ux_upload_init', 'POST', 1)?->getStatusCode());
        self::assertSame(429, $this->send('app_demo_upload_keep_files', 'POST', 1)?->getStatusCode());
    }

    public function testAnotherVisitorIsNotAffected(): void
    {
        $this->send('ux_upload_direct', 'POST', 900);
        $this->send('ux_upload_direct', 'POST', 900);

        self::assertNull($this->send('ux_upload_direct', 'POST', 900, '198.51.100.20'));
    }

    public function testARequestLargerThanTheWholeAllowanceIsRefused(): void
    {
        self::assertSame(429, $this->send('app_demo_upload_keep_files', 'POST', 50_000)?->getStatusCode());
    }

    public function testABlockedVisitorIsRefusedBeforeTheDiskIsMeasured(): void
    {
        $this->send('ux_upload_direct', 'POST', 900);
        $this->send('ux_upload_direct', 'POST', 900);
        file_put_contents($this->chunk, str_repeat('x', 1500));

        self::assertSame(429, $this->send('ux_upload_init', 'POST', 1)?->getStatusCode());
        self::assertSame(0, $this->storage->calls);
    }

    public function testAnUploadPassesWhileTheDirectoryIsUnderItsBudget(): void
    {
        file_put_contents($this->chunk, str_repeat('x', 400));

        self::assertNull($this->send('ux_upload_init', 'POST'));
        self::assertSame(0, $this->storage->calls, 'Nothing to prune while there is room.');
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('provideWrites')]
    public function testNewDataIsRefusedOnceTheBudgetIsSpent(string $route, string $method, int $status, array $headers): void
    {
        file_put_contents($this->chunk, str_repeat('x', 1500));

        $refusal = $this->send($route, $method);

        self::assertSame($status, $refusal?->getStatusCode());
        self::assertStringContainsString('The demo server is full', (string) $refusal->getContent());
        foreach ($headers as $name => $value) {
            self::assertSame($value, $refusal->headers->get($name));
        }
        self::assertSame(1, $this->storage->calls, 'Expired files are pruned before anything is refused.');
    }

    /**
     * @return iterable<string, array{string, string, int, array<string, string>}>
     */
    public static function provideWrites(): iterable
    {
        yield 'new upload' => ['ux_upload_init', 'POST', 507, ['Content-Type' => 'application/json']];
        yield 'small file sent in one request' => ['ux_upload_direct', 'POST', 507, ['Content-Type' => 'application/json']];
        yield 'chunk of an upload in progress' => ['ux_upload_handle', 'PUT', 507, ['Content-Type' => 'application/json']];
        yield 'demo form sent without JavaScript' => ['app_demo_upload_keep_files', 'POST', 503, ['Retry-After' => '300']];
        yield 'package page form' => ['app_upload', 'POST', 503, ['Retry-After' => '300']];
    }

    public function testPruningExpiredFilesMakesRoomAgain(): void
    {
        file_put_contents($this->chunk, str_repeat('x', 1500));
        $storage = new PruningSpy(fn () => unlink($this->chunk));

        $event = $this->event('ux_upload_init', 'POST');
        $this->listener($storage)->onRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame([UploadDemoQuotaListener::STALE_SESSION_AGE], $storage->ages);
    }

    #[DataProvider('provideHarmlessRequests')]
    public function testOtherRequestsAreNeverRefused(string $route, string $method): void
    {
        file_put_contents($this->chunk, str_repeat('x', 1500));
        $this->send('ux_upload_direct', 'POST', 900);
        $this->send('ux_upload_direct', 'POST', 900);
        $this->storage->calls = 0;

        self::assertNull($this->send($route, $method, 900));
        self::assertSame(0, $this->storage->calls);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideHarmlessRequests(): iterable
    {
        yield 'reading a demo page' => ['app_demo_upload_keep_files', 'GET'];
        yield 'removing an upload frees space' => ['ux_upload_remove', 'DELETE'];
        yield 'cancelling an upload frees space' => ['ux_upload_handle', 'DELETE'];
        yield 'another form of the site' => ['app_demo_live_component_upload', 'POST'];
    }

    private function listener(PruningSpy $storage): UploadDemoQuotaListener
    {
        // 1000 bytes per network and per window, 1000 bytes of disk for everyone
        $allowance = new RateLimiterFactory(['id' => 'upload_demo_bytes', 'policy' => 'sliding_window', 'limit' => 1000, 'interval' => '10 minutes'], new InMemoryStorage());

        return new UploadDemoQuotaListener($allowance, new ArrayAdapter(), $storage, $this->directory, 1000);
    }

    private function send(string $route, string $method, int $bytes = 0, string $ip = self::VISITOR): ?Response
    {
        $event = $this->event($route, $method, $bytes, $ip);
        $this->listener->onRequest($event);

        return $event->getResponse();
    }

    private function event(string $route, string $method, int $bytes = 0, string $ip = self::VISITOR): RequestEvent
    {
        $request = Request::create('/', $method, server: ['REMOTE_ADDR' => $ip, 'CONTENT_LENGTH' => (string) $bytes]);
        $request->attributes->set('_route', $route);

        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
