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

use App\Service\Changelog\ChangelogProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChangelogController extends AbstractController
{
    public function __construct(
        private readonly ChangelogProvider $changeLogProvider,
    ) {
    }

    #[Route('/changelog', name: 'app_changelog', defaults: ['_format' => 'html'])]
    #[Route('/changelog.md', name: 'app_changelog_md', defaults: ['_format' => 'md'])]
    public function __invoke(string $_format): Response
    {
        $changelog = $this->changeLogProvider->getChangelog();

        $response = new Response();
        if ('md' === $_format) {
            $response->headers->set('Content-Type', 'text/markdown');
        }

        return $this->render('changelog.'.$_format.'.twig', [
            'changelog' => $changelog,
        ], $response);
    }
}
