<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components;

use App\Form\AvatarUploadForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
use Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait;
use Symfony\UX\Upload\Upload\UploadedFile;

#[AsLiveComponent]
final class UploadAvatar
{
    use ComponentWithUploadTrait {
        getUpload as private resolveUpload;
    }
    use DefaultActionTrait;

    #[LiveProp]
    #[UploadTarget(uploader: 'avatars')]
    public ?string $photo = null;

    public function __construct(private readonly FormFactoryInterface $formFactory)
    {
    }

    public function getUpload(string $property): ?UploadedFile
    {
        return $this->resolveUpload($property);
    }

    public function getUploadForm(): FormView
    {
        return $this->formFactory->create(AvatarUploadForm::class)->createView();
    }
}
