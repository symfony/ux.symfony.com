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

use App\EventListener\SlowDemoUploadListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\UX\Upload\Event\UploadStartedEvent;

final class SlowDemoUploadListenerTest extends TestCase
{
    private LockFactory $locks;
    private MockClock $clock;
    private SlowDemoUploadListener $listener;

    protected function setUp(): void
    {
        $this->locks = new LockFactory(new InMemoryStore());
        $this->clock = new MockClock();
        $this->listener = new SlowDemoUploadListener(new ArrayAdapter(), $this->locks, $this->clock);
        $this->listener->onUploadStarted(new UploadStartedEvent('upload-1', SlowDemoUploadListener::FILENAME, 1_048_576, 'application/zip', 16, '/_ux/upload/upload-1', 65_536, false, 2));
    }

    public function testAChunkOfTheSampleFileIsSlowedDown(): void
    {
        self::assertEqualsWithDelta(.7, $this->delayOf('upload-1'), .001);
    }

    public function testTheChunksOfAnyOtherFileAreNot(): void
    {
        self::assertSame(0.0, $this->delayOf('upload-2'));
    }

    public function testNoMoreThanTwoWorkersWaitAtTheSameTime(): void
    {
        // Two chunks are already waiting: a third one must not hold one more PHP worker
        $first = $this->locks->createLock(SlowDemoUploadListener::SLOT.'0');
        $second = $this->locks->createLock(SlowDemoUploadListener::SLOT.'1');
        self::assertTrue($first->acquire());
        self::assertTrue($second->acquire());

        self::assertSame(0.0, $this->delayOf('upload-1'));
    }

    /**
     * The time the listener asks the clock to wait for one chunk: the mock clock moves, the test does not.
     */
    private function delayOf(string $uploadId): float
    {
        $request = Request::create('/_ux/upload/'.$uploadId, 'PUT');
        $request->attributes->set('_route', 'ux_upload_handle');
        $request->attributes->set('uploadId', $uploadId);
        $start = (float) $this->clock->now()->format('U.u');

        $this->listener->onRequest(new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        return (float) $this->clock->now()->format('U.u') - $start;
    }
}
