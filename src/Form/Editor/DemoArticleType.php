<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Editor;

use App\Entity\Editor\DemoArticle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Bridge\CKEditor\Config\CKEditorConfig;
use Symfony\UX\Editor\Config\CommonOptions;
use Symfony\UX\Editor\Form\EditorType;

final class DemoArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('title', TextType::class, ['required' => true])
            ->add('body', EditorType::class, [
                'config' => new CKEditorConfig(
                    common: new CommonOptions(
                        toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList'],
                        placeholder: 'Write your article…',
                    ),
                    licenseKey: 'GPL',
                ),
            ])
            ->add('save', SubmitType::class, ['label' => 'Save', 'attr' => ['data-test-id' => 'submit-button']]);
    }

    public function configureOptions(OptionsResolver $r): void
    {
        $r->setDefaults(['data_class' => DemoArticle::class]);
    }
}
