<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceAutocompleteChoiceType;

final class ResourceAutocompleteChoiceField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setFormType(ResourceAutocompleteChoiceType::class)
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/resourceChoice/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/resourceChoice/grid.html.twig')
            ->setMultiple()
            ->setLabel($label);
    }

    public function setResource(string $resource): self
    {
        $this->setFormTypeOption('resource', $resource);

        return $this;
    }

    public function setMultiple(bool $multiple = true): self
    {
        $this->setFormTypeOption('multiple', $multiple);

        return $this;
    }

    public function setChoiceValue(string $choiceValue): self
    {
        $this->setFormTypeOption('choice_value', $choiceValue);

        return $this;
    }

    public function setChoiceName(string $choiceName): self
    {
        $this->setFormTypeOption('choice_name', $choiceName);

        return $this;
    }

    public function setRemoteRoute(string $path, array $parameters = []): self
    {
        $this->setFormTypeOption('remote_route', [
            'path' => $path,
            'parameters' => $parameters,
        ]);

        return $this;
    }

    public function setLoadEditRoute(string $path, array $parameters = []): self
    {
        $this->setFormTypeOption('load_edit_route', [
            'path' => $path,
            'parameters' => $parameters,
        ]);

        return $this;
    }

    public function setRepositoryMethod(string $method): self
    {
        $this->setFormTypeOption('repositoryMethod', $method);

        return $this;
    }

    public function setRepositoryArguments(array $arguments): self
    {
        $this->setFormTypeOption('repositoryArguments', $arguments);

        return $this;
    }

    public function setRemoteCriteriaName(string $criteriaName): self
    {
        $this->setFormTypeOption('remote_criteria_name', $criteriaName);

        return $this;
    }

}
