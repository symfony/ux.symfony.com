<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\SyntaxError;

class CssController extends AbstractController
{
    private const MISTAKES = [
        "<h3 class=\"{{ css({ colr: 'fg' }) }}\">",
        "<div class=\"{{ css({ bg: 'surfac' }) }}\">",
        "<div class=\"{{ css({ display: 'flexx' }) }}\">",
        "<a class=\"{{ css({ _hovr: { bg: 'blue.700' } }) }}\">",
        "<p class=\"{{ css({ maxW: '37ch' }) }}\">",
    ];

    #[Route('/css', name: 'app_css')]
    public function __invoke(UxPackageRepository $packageRepository, Environment $twig): Response
    {
        $package = $packageRepository->find('css');

        $mistakes = [];
        foreach (self::MISTAKES as $source) {
            try {
                $twig->createTemplate($source);
            } catch (SyntaxError $e) {
                $mistakes[] = $source."\n{# ".$e->getRawMessage().' #}';
            }
        }

        return $this->render('ux_packages/css.html.twig', [
            'package' => $package,
            'mistakes' => implode("\n\n", $mistakes),
        ]);
    }
}
