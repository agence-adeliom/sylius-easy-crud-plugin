<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\RowType;

final class RowField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName = '', ?string $label = null): self
    {
        return (new self())
            ->setProperty('easy_crud_row_' . rand(0, 10000))
            ->setFormType(RowType::class)
            ->addFormThemes(RowType::configureAdminFormThemes())
            ->hideOnIndex()
            ->setFormTypeOption('mapped', false)
        ;
    }
}
