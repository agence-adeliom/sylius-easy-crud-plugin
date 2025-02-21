<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractType extends \Symfony\Component\Form\AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('field', null);
        $resolver->setDefault('columns', 'col-12');
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['columns'] = $options['columns'];
    }
}
