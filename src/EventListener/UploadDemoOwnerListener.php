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

use App\Service\UploadDemoContextResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Names the visitor when a page with an upload field is requested.
 *
 * Several files upload in parallel. Without an owner already in the session,
 * each request would create its own, and the form would refuse all but one.
 */
final class UploadDemoOwnerListener
{
    public function __construct(
        private readonly UploadDemoContextResolver $contextResolver,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->getString('_route');
        if ('app_upload' === $route || str_starts_with($route, 'app_demo_upload_')) {
            $this->contextResolver->resolve();
        }
    }
}
