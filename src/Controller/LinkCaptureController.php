<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Catch-all endpoint used by the Toolkit `common` kit previews.
 *
 * The `PostLink` / `LogoutLink` components submit real forms; pointing them at
 * `/link/...` lets us render back the request details (method, spoofed method,
 * CSRF token, body) so the previews are interactive instead of hitting a 404.
 */
class LinkCaptureController extends AbstractController
{
    #[Route('/link/{path}', name: 'app_link_capture', requirements: ['path' => '.+'])]
    public function capture(Request $request, string $path): Response
    {
        // Only serve same-origin iframe navigations (i.e. the Toolkit previews).
        // These Fetch Metadata headers are set by the browser and cannot be
        // forged by page scripts, so a direct visit or cross-site embed 404s.
        if ('iframe' !== $request->headers->get('Sec-Fetch-Dest')
            || 'same-origin' !== $request->headers->get('Sec-Fetch-Site')
        ) {
            throw $this->createNotFoundException();
        }

        $response = $this->render('link/capture.html.twig', [
            'path' => $path,
            'method' => $request->getMethod(),
            'spoofed_method' => $request->request->get('_method'),
            'csrf_token' => $request->request->get('_csrf_token'),
            'body' => $request->request->all(),
            'query' => $request->query->all(),
        ]);

        // Only our own pages may frame this response (browser-enforced).
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");

        return $response;
    }
}
