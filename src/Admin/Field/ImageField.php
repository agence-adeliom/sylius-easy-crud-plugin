<?php

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\ImageType;

final class ImageField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = 'sylius.form.image.file'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->addFormThemes(ImageType::configureAdminFormThemes())
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/image/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/image/grid.html.twig')
            ->addAssets(ImageType::configureAdminAssets())
            ->setFormType(ImageType::class)
            ->setFormTypeOption('attr', ['accept' => 'image/*'])
            ->setFormTypeOption('data_class', null);
    }
}
