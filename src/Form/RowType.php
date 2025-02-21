<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class RowType extends AbstractType implements AdminFormTypeInterface
{
    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'easy_crud_row';
    }

    /**
     * @return string[]
     */
    public static function configureAdminFormThemes(): array
    {
        return ['@SyliusEasyCrudPlugin/field/row/form.html.twig'];
    }

    public static function configureAdminAssets(): array
    {
        return [];
    }
}
