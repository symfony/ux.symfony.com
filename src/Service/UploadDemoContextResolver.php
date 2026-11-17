<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Upload\Security\UploadContext;
use Symfony\UX\Upload\Security\UploadContextResolverInterface;

final class UploadDemoContextResolver implements UploadContextResolverInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function resolve(): UploadContext
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request || !$request->hasSession()) {
            return new UploadContext();
        }

        $session = $request->getSession();
        $owner = $session->get('_ux_upload_owner');
        if (!\is_string($owner)) {
            $owner = bin2hex(random_bytes(16));
            $session->set('_ux_upload_owner', $owner);
        }

        return new UploadContext(ownerId: $owner);
    }
}
