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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Upload\UploadedFile;

/**
 * @extends AbstractType<array{classic?: HttpUploadedFile, upload?: UploadedFile, title?: string}>
 */
class KeepFilesForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // The file travels with the form.
            ->add('classic', FileType::class, [
                'label' => 'Document',
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '1M',
                        mimeTypes: 'application/pdf',
                    ),
                ],
            ])
            // The file travels first, the form carries a reference.
            ->add('upload', UploadType::class, [
                'label' => 'Document',
                'uploader' => 'documents',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(message: 'Please enter a title.'),
                ],
            ])
        ;
    }
}
