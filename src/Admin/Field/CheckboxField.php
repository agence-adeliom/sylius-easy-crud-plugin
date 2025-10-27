<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;

final class CheckboxField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/yesno/grid.html.twig')
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/yesno/show.html.twig')
            ->setLabel($label);
    }
}
