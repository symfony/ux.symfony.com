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
 * @extends AbstractType<array{photos?: list<UploadedFile>}>
 */
class UploadCardGridForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('photos', UploadType::class, [
            'uploader' => 'gallery',
            'layout' => 'dropzone',
            'multiple' => true,
            'max_files' => 8,
            'show_preview' => true,
        ]);
    }
}
