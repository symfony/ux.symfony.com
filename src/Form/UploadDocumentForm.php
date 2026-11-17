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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Upload\UploadedFile;

/**
 * @extends AbstractType<array{title?: string, document?: UploadedFile}>
 */
class UploadDocumentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank(message: 'Please enter a title.')],
                'attr' => [
                    'placeholder' => 'Document title',
                ],
            ])
            ->add('document', UploadType::class, [
                'uploader' => 'documents',
                'layout' => 'dropzone',
            ])
        ;
    }
}
