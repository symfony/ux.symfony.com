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

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Upload\Token\UploadTokenHandler;

/**
 * Destroys a visitor's file the moment its upload completes.
 *
 * This server is public: it keeps no file. The browser holds a signed reference
 * with the name, type and size, and every demo runs on that reference alone.
 * A real application keeps the file until its form is saved, then moves it.
 */
final class UploadDemoDestroyListener
{
    public function __construct(private readonly UploadTokenHandler $tokenHandler)
    {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        // One request for a small file, or the last request of a file sent in chunks
        if (!$request->isMethod('POST')
            || !\in_array($request->attributes->get('_route'), ['ux_upload_direct', 'ux_upload_handle'], true)
            || !$response->isSuccessful()
        ) {
            return;
        }

        $token = json_decode((string) $response->getContent(), true)['token'] ?? null;
        if (\is_string($token)) {
            $this->tokenHandler->resolveForLiveTarget($token)?->delete();
        }
    }
}
