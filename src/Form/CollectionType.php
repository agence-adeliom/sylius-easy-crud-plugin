<?php

declare(strict_types=1);

/**
 * This Collection only add the missing labels which
 * aren't checked for defined in the Sylius template
 */

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends \Symfony\Component\Form\Extension\Core\Type\CollectionType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);
        $view->vars['button_add_label'] = $options['button_add_label'];
        $view->vars['button_delete_label'] = $options['button_delete_label'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'button_add_label' => 'sylius.form.collection.add',
            'button_delete_label' => 'sylius.form.collection.delete',
        ]);
    }
}
