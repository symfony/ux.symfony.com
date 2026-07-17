<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Stubs the Twig functions normally provided by SecurityBundle.
 *
 * This demo site has no firewall configured, but the Toolkit `common` kit
 * components (e.g. LogoutLink, PostLink) call `logout_path()` and
 * `csrf_token()`. These stubs return placeholder values so the component
 * previews can render without SecurityBundle / symfony/security-csrf.
 */
final class SecurityStubExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('logout_path', static fn (?string $key = null): string => '/link/logout'.($key ? '/'.$key : ''));
        // Overrides the twig-bridge `csrf_token`, which needs a CsrfTokenManager.
        yield new TwigFunction('csrf_token', static fn (string $tokenId): string => 'stub.'.$tokenId);
    }
}
