<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractType extends \Symfony\Component\Form\AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('field', null);
    }
}
