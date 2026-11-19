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

use App\Model\LiveDemo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demos/upload')]
final class LiveAvatarDemoController extends AbstractController
{
    #[Route('/live-avatar', name: 'app_demo_upload_live_avatar')]
    public function liveAvatar(): Response
    {
        $demo = new LiveDemo(
            'upload-live-avatar',
            name: 'Live Avatar',
            description: 'Update a LiveComponent when the upload completes.',
            author: 'smnandre',
            publishedAt: '2026-11-15',
            tags: ['upload', 'LiveComponent', 'avatar'],
            longDescription: 'When an [`UploadType`](/upload) field completes its upload, the signed reference lands on an
                `#[UploadTarget]` prop and the component renders again with the name and size
                it received. No form submission, no page reload. The picture is the preview
                of your browser: this public server keeps no file and sends none back.',
            route: 'app_demo_upload_live_avatar',
            template: 'demos/upload/live_avatar.html.twig',
        );

        return $this->render($demo->getTemplate(), [
            'demo' => $demo,
        ]);
    }
}
