<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\KeyValueStore;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
trait FieldTrait
{
    private FieldDto $dto;

    private function __construct()
    {
        $this->dto = new FieldDto();
        $this->dto->setFormTypeOption('required', false);
    }

    public function setFieldFqcn(string $fieldFqcn): self
    {
        $this->dto->setFieldFqcn($fieldFqcn);

        return $this;
    }

    public function setProperty(string $propertyName): self
    {
        $this->dto->setProperty($propertyName);

        return $this;
    }

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public function setLabel($label): self
    {
        $this->dto->setLabel($label);

        return $this;
    }

    public function setValue($value): self
    {
        $this->dto->setValue($value);

        return $this;
    }

    public function setFormattedValue($value): self
    {
        $this->dto->setFormattedValue($value);

        return $this;
    }

    public function formatValue(?callable $callable): self
    {
        $this->dto->setFormatValueCallable($callable);

        return $this;
    }

    public function setVirtual(?bool $isVirtual = true): self
    {
        $this->dto->setVirtual($isVirtual);

        return $this;
    }

    public function setDisabled(bool $disabled = true): self
    {
        $this->dto->setFormTypeOption('disabled', $disabled);

        return $this;
    }

    public function setRequired(bool $isRequired): self
    {
        $this->dto->setFormTypeOption('required', $isRequired);

        return $this;
    }

    public function setEmptyData($emptyData = null): self
    {
        $this->dto->setFormTypeOption('empty_data', $emptyData);

        return $this;
    }

    public function setFormType(string $formTypeFqcn): self
    {
        $this->dto->setFormType($formTypeFqcn);

        return $this;
    }

    public function setFormTypeOptions(array $options): self
    {
        $this->dto->setFormTypeOptions($options);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, $optionValue): self
    {
        $this->dto->setFormTypeOptionIfNotSet($optionName, $optionValue);

        return $this;
    }

    public function setSortable(bool $isSortable): self
    {
        $this->dto->setSortable($isSortable);

        return $this;
    }

    public function setSortablePath(string $sortablePath): self
    {
        $this->dto->setSortablePath($sortablePath);

        return $this;
    }

    public function setPermission(string $permission): self
    {
        $this->dto->setPermission($permission);

        return $this;
    }

    public function setHelp(TranslatableInterface|string $help): self
    {
        $this->dto->setHelp($help);

        return $this;
    }

    public function addCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($this->dto->getCssClass() . ' ' . $cssClass);

        return $this;
    }

    public function setCssClass(string $cssClass): self
    {
        $this->dto->setCssClass($cssClass);

        return $this;
    }

    public function setTranslationParameters(array $parameters): self
    {
        $this->dto->setTranslationParameters($parameters);

        return $this;
    }

    public function setGridTemplatePath(string $path): self
    {
        $this->dto->setGridTemplatePath($path);

        return $this;
    }

    public function setShowTemplatePath(string $path): self
    {
        $this->dto->setShowTemplatePath($path);

        return $this;
    }

    public function addFormThemes(array $formThemePaths): self
    {
        $this->dto->addFormThemes($formThemePaths);

        return $this;
    }

    public function addFormTheme(string ...$formThemePaths): self
    {
        foreach ($formThemePaths as $formThemePath) {
            $this->dto->addFormTheme($formThemePath);
        }

        return $this;
    }

    public function addWebpackEncoreEntries(Asset|string ...$entryNamesOrAssets): self
    {
        if (!class_exists('Symfony\\WebpackEncoreBundle\\Twig\\EntryFilesTwigExtension')) {
            throw new \RuntimeException('You are trying to add Webpack Encore entries in a field but Webpack Encore is not installed in your project. Try running "composer require symfony/webpack-encore-bundle"');
        }

        foreach ($entryNamesOrAssets as $entryNameOrAsset) {
            if (\is_string($entryNameOrAsset)) {
                $this->dto->addWebpackEncoreAsset(new AssetDto($entryNameOrAsset));
            } else {
                $this->dto->addWebpackEncoreAsset($entryNameOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addCssFiles(Asset|string|array ...$pathsOrAssets): self
    {
        if (is_array($pathsOrAssets[0])) {
            $pathsOrAssets = $pathsOrAssets[0];
        }
        foreach ($pathsOrAssets as $pathOrAsset) {
            if (\is_string($pathOrAsset)) {
                $this->dto->addCssAsset(new AssetDto($pathOrAsset));
            } elseif (null !== $pathOrAsset) {
                $this->dto->addCssAsset($pathOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addAssets(array $assets): self
    {
        if (isset($assets['js'])) {
            $this->addJsFiles($assets['js']);
        }
        if (isset($assets['css'])) {
            $this->addCssFiles($assets['css']);
        }
        if (isset($assets['webpack'])) {
            $this->addWebpackEncoreEntries($assets['webpack']);
        }

        return $this;
    }

    public function addJsFiles(Asset|string|array ...$pathsOrAssets): self
    {
        if (is_array($pathsOrAssets[0])) {
            $pathsOrAssets = $pathsOrAssets[0];
        }
        foreach ($pathsOrAssets as $pathOrAsset) {
            if (\is_string($pathOrAsset)) {
                $this->dto->addJsAsset(new AssetDto($pathOrAsset));
            } elseif (null !== $pathOrAsset) {
                $this->dto->addJsAsset($pathOrAsset->getAsDto());
            }
        }

        return $this;
    }

    public function addHtmlContentsToHead(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToHead($content);
        }

        return $this;
    }

    public function addHtmlContentsToBody(string ...$contents): self
    {
        foreach ($contents as $content) {
            $this->dto->addHtmlContentToBody($content);
        }

        return $this;
    }

    public function setCustomOption(string $optionName, $optionValue): self
    {
        $this->dto->setCustomOption($optionName, $optionValue);

        return $this;
    }

    public function setCustomOptions(array $options): self
    {
        $this->dto->setCustomOptions($options);

        return $this;
    }

    public function hideOnDetail(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_DETAIL);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnForm(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_NEW);
        $displayedOn->delete(Crud::PAGE_EDIT);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideWhenCreating(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_NEW);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideWhenUpdating(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_EDIT);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function hideOnIndex(): self
    {
        $displayedOn = $this->dto->getDisplayedOn();
        $displayedOn->delete(Crud::PAGE_INDEX);

        $this->dto->setDisplayedOn($displayedOn);

        return $this;
    }

    public function onlyOnDetail(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_DETAIL => Crud::PAGE_DETAIL]));

        return $this;
    }

    public function onlyOnForms(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([
                                                          Crud::PAGE_NEW => Crud::PAGE_NEW,
                                                          Crud::PAGE_EDIT => Crud::PAGE_EDIT,
                                                      ]));

        return $this;
    }

    public function onlyOnIndex(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_INDEX => Crud::PAGE_INDEX]));

        return $this;
    }

    public function onlyWhenCreating(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_NEW => Crud::PAGE_NEW]));

        return $this;
    }

    public function onlyWhenUpdating(): self
    {
        $this->dto->setDisplayedOn(KeyValueStore::new([Crud::PAGE_EDIT => Crud::PAGE_EDIT]));

        return $this;
    }

    /**
     * @param int|string $cols An integer with the number of columns that this field takes (e.g. 6),
     *                         or a string with responsive col CSS classes (e.g. 'col-6 col-sm-4 col-lg-3')
     */
    public function setColumns(int|string $cols): self
    {
        $this->dto->setColumns(\is_int($cols) ? 'col-md-' . $cols : $cols);

        return $this;
    }

    /**
     * Used to define the columns of fields when users don't define the
     * columns explicitly using the setColumns() method.
     * This should only be used if you create a custom EasyAdmin field,
     * not when configuring fields in your backend.
     *
     * @internal
     */
    public function setDefaultColumns(int|string $cols): self
    {
        $this->dto->setDefaultColumns(\is_int($cols) ? 'col-md-' . $cols : $cols);

        return $this;
    }

    public function getAsDto(): FieldDto
    {
        return $this->dto;
    }

    public static function create(string $propertyName, $label = null): \Sylius\Bundle\GridBundle\Builder\Field\FieldInterface
    {
        $field = self::new($propertyName, $label);
        $syliusField = TwigField::create($propertyName, 'twig');
        $syliusField->setOptions([
            'template' => $field->getAsDto()->getGridTemplatePath(),
            'vars' => $field->getAsDto()->getFormTypeOptions(),
        ]);

        return $syliusField;
    }
}
