<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\SlugType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class SlugField implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->addFormThemes(SlugType::configureAdminFormThemes())
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/default/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/slug/grid.html.twig')
            ->setFormType(SlugType::class)
            ->addCssClass('field-slug')
            ->addAssets(SlugType::configureAdminAssets())
            ->setDefaultColumns('col-md-12 col-xxl-10')
        ;
    }
}
