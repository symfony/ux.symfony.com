<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\UX\Upload\Storage\PrunableStorageInterface;

/**
 * Refuses new data for the upload demos once a quota is spent: one per network, one for everyone.
 *
 * The demos are public and the disk is small. Size and type limits apply to one file.
 * The allowance applies to what one network sends over time: it is counted per IP
 * address, not per session, because a session is free to renew. The disk budget
 * applies to all the files together.
 */
final class UploadDemoQuotaListener
{
    /**
     * An upload that received no chunk for this long is abandoned.
     */
    public const STALE_SESSION_AGE = 600;

    private const BLOCK_SECONDS = 900;

    public function __construct(
        #[Autowire(service: 'limiter.upload_demo_bytes')]
        private readonly RateLimiterFactoryInterface $allowance,
        #[Autowire(service: 'cache.rate_limiter')]
        private readonly CacheItemPoolInterface $cache,
        private readonly PrunableStorageInterface $storage,
        #[Autowire('%app.upload_demo_dir%')]
        private readonly string $directory,
        #[Autowire('%app.upload_demo_disk_budget%')]
        private readonly int $budget,
    ) {
    }

    // After the router, before SlowDemoUploadListener: a refused request holds no PHP worker
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 8)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !self::receivesData($request)) {
            return;
        }

        // The network first: one visitor is stopped alone, and costs no disk measure
        if ($this->networkIsBlocked($request)) {
            $event->setResponse(self::refuse($request, 'Too many uploads from your network. Try again in 15 minutes.', Response::HTTP_TOO_MANY_REQUESTS, Response::HTTP_TOO_MANY_REQUESTS, self::BLOCK_SECONDS));
        } elseif ($this->diskIsFull()) {
            $event->setResponse(self::refuse($request, 'The demo server is full. Try again in a few minutes.', Response::HTTP_INSUFFICIENT_STORAGE, Response::HTTP_SERVICE_UNAVAILABLE, 300));
        }
    }

    /**
     * A request that brings data to the upload demos: through the upload endpoints,
     * or with a demo form sent without JavaScript.
     */
    private static function receivesData(Request $request): bool
    {
        $route = $request->attributes->getString('_route');

        return \in_array($request->getMethod(), ['POST', 'PUT'], true)
            && (str_starts_with($route, 'ux_upload_') || 'app_upload' === $route || str_starts_with($route, 'app_demo_upload_'));
    }

    private function networkIsBlocked(Request $request): bool
    {
        $network = hash('xxh128', $request->getClientIp() ?? 'unknown');
        $block = $this->cache->getItem('upload_demo_blocked_'.$network);
        if ($block->isHit()) {
            return true;
        }

        try {
            $accepted = $this->allowance->create($network)->consume(max(1, (int) $request->headers->get('Content-Length')))->isAccepted();
        } catch (\InvalidArgumentException) {
            // A single request larger than the whole allowance
            $accepted = false;
        }
        if (!$accepted) {
            $this->cache->save($block->set(true)->expiresAfter(self::BLOCK_SECONDS));
        }

        return !$accepted;
    }

    private function diskIsFull(): bool
    {
        if ($this->used() < $this->budget) {
            return false;
        }

        // Expired files only leave with the cleanup: run it before refusing anyone
        $this->storage->prune(self::STALE_SESSION_AGE);

        return $this->used() >= $this->budget;
    }

    private function used(): int
    {
        if (!is_dir($this->directory)) {
            return 0;
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            try {
                $size += $file->isFile() ? $file->getSize() : 0;
            } catch (\RuntimeException) {
                // Deleted between the listing and the measure
            }
        }

        return $size;
    }

    /**
     * The upload endpoints answer the field's JavaScript, a demo form answers the visitor.
     */
    private static function refuse(Request $request, string $message, int $endpointStatus, int $formStatus, int $retryAfter): Response
    {
        $headers = ['Retry-After' => (string) $retryAfter];

        return str_starts_with($request->attributes->getString('_route'), 'ux_upload_')
            ? new JsonResponse(['error' => $message], $endpointStatus, $headers)
            : new Response($message, $formStatus, $headers + ['Content-Type' => 'text/plain']);
    }
}
