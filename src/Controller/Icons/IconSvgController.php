<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Icons;

use App\Service\Icon\JsDelivr;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IconSvgController extends AbstractController
{
    #[Route(
        '/icon/{prefix}/{name}.svg',
        name: 'app_icon_svg',
        requirements: ['prefix' => '[a-z0-9-]+', 'name' => '[a-z0-9-]+'],
        methods: ['GET'],
    )]
    public function __invoke(string $prefix, string $name, JsDelivr $jsDelivr): Response
    {
        $svg = $jsDelivr->svg($prefix, $name) ?? throw $this->createNotFoundException(\sprintf('Unknown icon "%s:%s".', $prefix, $name));

        $response = new Response($svg, headers: ['Content-Type' => 'image/svg+xml']);
        $response->setPublic();
        $response->setMaxAge(604800);
        $response->setImmutable();

        return $response;
    }
}
