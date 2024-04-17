<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceChoiceType;

final class ResourceChoiceField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setFormType(ResourceChoiceType::class)
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/resourceChoice/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/resourceChoice/grid.html.twig')
            ->setFormTypeOption('useResourceTransformers', false)
            ->setLabel($label);
    }

    public function setResource(string $resource): self
    {
        $this->setFormTypeOption('resource', $resource);

        return $this;
    }

    public function setMultiple(bool $multiple = false): self
    {
        $this->setFormTypeOption('multiple', $multiple);

        return $this;
    }

    public function setChoiceValue(string $choiceValue): self
    {
        $this->setFormTypeOption('choice_value', $choiceValue);

        return $this;
    }

    public function setChoiceLabel(string | bool | callable | null $choiceLabel): self
    {
        $this->setFormTypeOption('choice_label', $choiceLabel);

        return $this;
    }

    public function setRepositoryMethod(?string $method = null): self
    {
        $this->setFormTypeOption('repositoryMethod', $method);

        return $this;
    }

    public function setRepositoryArguments(?array $arguments = null): self
    {
        $this->setFormTypeOption('repositoryArguments', $arguments);

        return $this;
    }
}
