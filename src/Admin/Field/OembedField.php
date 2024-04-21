<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\OembedType;

final class OembedField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null, $fieldsConfig = []): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(OembedType::class)
            ->addFormThemes(OembedType::configureAdminFormThemes())
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/oembed/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/oembed/grid.html.twig')
        ;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->setFormTypeOption('required', $isRequired);

        return $this;
    }
}
