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

use App\Entity\Editor\DemoNote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Editor\Form\EditorType;

final class DemoNoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('title', TextType::class, ['required' => true])
            ->add('body', EditorType::class, ['preset' => 'blog.standard'])
            ->add('save', SubmitType::class, ['label' => 'Save', 'attr' => ['data-test-id' => 'submit-button']]);
    }

    public function configureOptions(OptionsResolver $r): void
    {
        $r->setDefaults(['data_class' => DemoNote::class]);
    }
}
