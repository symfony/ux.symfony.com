<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Upload\UploadedFile;

/**
 * @extends AbstractType<array{file?: UploadedFile}>
 */
class UploadLargeFileForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // The "large" uploader sends 64 KB chunks, two at a time (config/packages/ux_upload.yaml).
        $builder->add('file', UploadType::class, [
            'uploader' => 'large',
            'layout' => 'dropzone',
            'help_text' => 'Up to 1 MB, sent in 64 KB chunks',
        ]);
    }
}
