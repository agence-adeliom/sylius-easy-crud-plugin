<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\IconType;

final class IconField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/icon/grid.html.twig')
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/icon/show.html.twig')
            ->addFormThemes(IconType::configureAdminFormThemes())
            ->setFormType(IconType::class)
            ->addAssets(IconType::configureAdminAssets())
        ;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->setFormTypeOption('required', $isRequired);

        return $this;
    }

    public function setSelectButtonLabel(string $label): self
    {
        $this->setFormTypeOption('select_button', $label);

        return $this;
    }

    public function setCancelButtonLabel(string $label): self
    {
        $this->setFormTypeOption('cancel_button', $label);

        return $this;
    }

    public function setShowAllButtonLabel(string $label): self
    {
        $this->setFormTypeOption('show_all_button', $label);

        return $this;
    }

    public function setSearchPlaceholder(string $label): self
    {
        $this->setFormTypeOption('search_placeholder', $label);

        return $this;
    }

    public function setNotResultMessage(string $message): self
    {
        $this->setFormTypeOption('no_result_found', $message);

        return $this;
    }

    public function setDeleteLabel(string $label): self
    {
        $this->setFormTypeOption('delete_label', $label);

        return $this;
    }

    /**
     * Path to css compiled fonts
     *
     * @param string|mixed[] $fonts
     */
    public function setFonts(string|array $fonts = []): self
    {
        $this->setFormTypeOption('fonts', $fonts);
        $this->resetAssets();
        $this->addAssets([
            'css' => is_string($fonts) ? [$fonts] : $fonts,
        ]);

        return $this;
    }

    /**
     * List of icons in JSON format
     */
    public function setJsonIconList(string $jsonUrl): self
    {
        $this->setFormTypeOption('json_url', $jsonUrl);

        return $this;
    }
}
