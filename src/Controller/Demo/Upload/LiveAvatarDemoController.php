<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Demo\Upload;

use App\Service\DemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demos/upload')]
final class LiveAvatarDemoController extends AbstractController
{
    #[Route('/live-avatar', name: 'app_demo_upload_live_avatar')]
    public function liveAvatar(DemoRepository $demoRepository): Response
    {
        $demo = $demoRepository->find('upload', 'live-avatar');

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
        ]);
    }
}
