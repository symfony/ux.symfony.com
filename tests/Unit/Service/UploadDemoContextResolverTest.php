<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Service\UploadDemoContextResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class UploadDemoContextResolverTest extends TestCase
{
    public function testNoRequestHasNoOwner(): void
    {
        self::assertNull(new UploadDemoContextResolver(new RequestStack())->resolve()->ownerId);
    }

    public function testNoSessionHasNoOwner(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request());

        self::assertNull(new UploadDemoContextResolver($stack)->resolve()->ownerId);
    }

    public function testOwnerIsStableAcrossRequestsInTheSameSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $stack = new RequestStack();
        $resolver = new UploadDemoContextResolver($stack);

        $request = new Request();
        $request->setSession($session);
        $stack->push($request);
        $owner = $resolver->resolve()->ownerId;
        self::assertNotNull($owner);
        $stack->pop();

        $nextRequest = new Request();
        $nextRequest->setSession($session);
        $stack->push($nextRequest);

        self::assertSame($owner, $resolver->resolve()->ownerId);
    }

    public function testVisitorsHaveDifferentOwners(): void
    {
        $stack = new RequestStack();
        $resolver = new UploadDemoContextResolver($stack);
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack->push($request);
        $firstOwner = $resolver->resolve()->ownerId;
        $stack->pop();

        $otherRequest = new Request();
        $otherRequest->setSession(new Session(new MockArraySessionStorage()));
        $stack->push($otherRequest);

        self::assertNotSame($firstOwner, $resolver->resolve()->ownerId);
    }
}
