<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\KeyValueStore;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use function Symfony\Component\String\u;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
final class FieldDto
{
    private ?string $fieldFqcn = null;

    private ?string $propertyName = null;

    private mixed $value = null;

    private TranslatableInterface|string|false|null $label;

    private ?string $formType = null;

    private KeyValueStore $formTypeOptions;

    private ?bool $sortable = null;

    private ?string $sortablePath = null;

    private ?bool $virtual = null;

    private ?string $permission = null;

    private ?string $textAlign = null;

    private TranslatableInterface|string|null $help;

    private string $cssClass = '';

    // how many columns the field takes when rendering
    // (defined as Bootstrap 5 grid classes; e.g. 'col-md-6 col-xxl-3')
    private ?string $columns = null;

    // same as $columns but used when the user doesn't define columns explicitly
    private string $defaultColumns = '';

    /** @var array<string, mixed> */
    private array $translationParameters = [];

    private ?string $templateName = 'crud/field/text';

    private ?string $templatePath = null;

    private ?string $gridTemplatePath = null;

    private ?string $showTemplatePath = null;

    /** @var array<int, string> */
    private array $formThemePaths = [];

    private AssetsDto $assets;

    private KeyValueStore $customOptions;

    private KeyValueStore $doctrineMetadata;

    /** @internal */
    private Ulid $uniqueId;

    private KeyValueStore $displayedOn;

    private ?FieldConfiguratorInterface $configurator = null;

    public function __construct()
    {
        $this->uniqueId = new Ulid();
        $this->assets = new AssetsDto();
        $this->formTypeOptions = KeyValueStore::new();
        $this->customOptions = KeyValueStore::new();
        $this->doctrineMetadata = KeyValueStore::new();
        $this->displayedOn = KeyValueStore::new([
            Crud::PAGE_INDEX => Crud::PAGE_INDEX,
            Crud::PAGE_DETAIL => Crud::PAGE_DETAIL,
            Crud::PAGE_NEW => Crud::PAGE_NEW,
            Crud::PAGE_EDIT => Crud::PAGE_EDIT,
        ]);
    }

    public function __clone()
    {
        $this->uniqueId = new Ulid();
        $this->assets = clone $this->assets;
        $this->formTypeOptions = clone $this->formTypeOptions;
        $this->customOptions = clone $this->customOptions;
        $this->doctrineMetadata = clone $this->doctrineMetadata;
        $this->displayedOn = clone $this->displayedOn;
    }

    public function getUniqueId(): Ulid
    {
        return $this->uniqueId;
    }

    public function getUniqueIdAsString(): string
    {
        return $this->uniqueId->toRfc4122();
    }

    public function setUniqueId(Ulid $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    public function isFormDecorationField(): bool
    {
        return u($this->getCssClass())->containsAny(['field-form_panel', 'field-form_tab']);
    }

    public function getFieldFqcn(): ?string
    {
        return $this->fieldFqcn;
    }

    /**
     * @internal Don't use this method yourself. EasyAdmin uses it internally
     *           to set the field FQCN. It's OK to use getFieldFqcn() to get this value.
     */
    public function setFieldFqcn(string $fieldFqcn): void
    {
        $this->fieldFqcn = $fieldFqcn;
    }

    public function getProperty(): string
    {
        return $this->propertyName;
    }

    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function getLabel(): ?string
    {
        if (is_string($this->label)) {
            return $this->label;
        }

        return null;
    }

    public function setLabel(TranslatableInterface|string|false|null $label): void
    {
        $this->label = $label;
        $this->setFormTypeOption('label', $label);
    }

    public function getFormType(): ?string
    {
        return $this->formType;
    }

    public function setFormType(string $formTypeFqcn): void
    {
        $this->formType = $formTypeFqcn;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions->all();
    }

    public function getFormTypeOption(string $optionName): mixed
    {
        return $this->formTypeOptions->get($optionName);
    }

    /**
     * @param array<string, mixed> $formTypeOptions
     */
    public function setFormTypeOptions(array $formTypeOptions): void
    {
        foreach ($formTypeOptions as $optionName => $optionValue) {
            $this->setFormTypeOption($optionName, $optionValue);
        }
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOption(string $optionName, mixed $optionValue): void
    {
        $this->formTypeOptions->set($optionName, $optionValue);
    }

    /**
     * @param string $optionName You can use "dot" notation to set nested options (e.g. 'attr.class')
     */
    public function setFormTypeOptionIfNotSet(string $optionName, mixed $optionValue): void
    {
        $this->formTypeOptions->setIfNotSet($optionName, $optionValue);
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $isSortable): void
    {
        $this->sortable = $isSortable;
    }

    public function getSortablePath(): ?string
    {
        return $this->sortablePath;
    }

    public function setSortablePath(string $sortablePath): void
    {
        $this->sortablePath = $sortablePath;
    }

    public function isVirtual(): ?bool
    {
        return $this->virtual;
    }

    public function setVirtual(bool $isVirtual): void
    {
        $this->virtual = $isVirtual;
        $this->formTypeOptions->set('mapped', !$isVirtual);
    }

    public function getTextAlign(): ?string
    {
        return $this->textAlign;
    }

    public function setTextAlign(string $textAlign): void
    {
        $this->textAlign = $textAlign;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }

    public function getHelp(): TranslatableInterface|string|null
    {
        return $this->help;
    }

    public function setHelp(TranslatableInterface|string $help): void
    {
        $this->help = $help;
        $this->setFormTypeOption('help', $help);
    }

    public function getCssClass(): string
    {
        return $this->cssClass;
    }

    public function setCssClass(string $cssClass): void
    {
        $this->cssClass = trim($cssClass);
    }

    public function getColumns(): ?string
    {
        return $this->columns;
    }

    public function setColumns(?string $columnCssClasses): void
    {
        $this->columns = $columnCssClasses;
    }

    public function getDefaultColumns(): string
    {
        return $this->defaultColumns;
    }

    public function setDefaultColumns(string $columnCssClasses): void
    {
        $this->defaultColumns = $columnCssClasses;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    /**
     * @param array<string, mixed> $translationParameters
     */
    public function setTranslationParameters(array $translationParameters): void
    {
        $this->translationParameters = $translationParameters;
    }

    public function getTemplateName(): ?string
    {
        return $this->templateName;
    }

    public function setTemplateName(?string $templateName): void
    {
        $this->templateName = $templateName;
    }

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    public function setTemplatePath(?string $templatePath): void
    {
        $this->templatePath = $templatePath;
    }

    public function getGridTemplatePath(): ?string
    {
        // '@SyliusUi/grid/field/rawLabel.html.twig'
        return $this->gridTemplatePath ?? '@SyliusEasyCrudPlugin/field/default/grid.html.twig';
    }

    public function setGridTemplatePath(?string $gridTemplatePath): void
    {
        $this->gridTemplatePath = $gridTemplatePath;
    }

    public function getShowTemplatePath(): ?string
    {
        // '@SyliusUi/grid/field/rawLabel.html.twig'
        return $this->showTemplatePath ?? '@SyliusEasyCrudPlugin/field/default/show.html.twig';
    }

    public function setShowTemplatePath(?string $showTemplatePath): void
    {
        $this->showTemplatePath = $showTemplatePath;
    }

    public function addFormTheme(string $formThemePath): void
    {
        $this->formThemePaths[] = $formThemePath;
    }

    /**
     * @param string[] $formThemes
     */
    public function addFormThemes(array $formThemes): void
    {
        $this->formThemePaths = array_merge($this->formThemePaths, $formThemes);
    }

    /**
     * @return string[]
     */
    public function getFormThemes(): array
    {
        return $this->formThemePaths;
    }

    /**
     * @param string[] $formThemePaths
     */
    public function setFormThemes(array $formThemePaths): void
    {
        $this->formThemePaths = $formThemePaths;
    }

    public function getAssets(): AssetsDto
    {
        return $this->assets;
    }

    public function setAssets(AssetsDto $assets): void
    {
        $this->assets = $assets;
    }

    /**
     * @param array<string, array<string, string|Asset>> $assets
     */
    public function addAssets(array $assets): void
    {
        if (isset($assets['js']) && is_array($assets['js'])) {
            foreach ($assets['js'] as $asset) {
                $found = false;
                foreach ($this->assets->getJsAssets() as $assetDto) {
                    if (is_string($asset)) {
                        if ($assetDto->getValue() === $asset) {
                            $found = true;
                        }
                    } elseif ($asset instanceof Asset) {
                        if ($assetDto->getValue() === $asset->getAsDto()->getValue()) {
                            $found = true;
                        }
                    }
                }
                if (!$found) {
                    if ($asset instanceof Asset) {
                        $this->assets->addJsAsset($asset->getAsDto());
                    } elseif (is_string($asset)) {
                        $this->assets->addJsAsset(new AssetDto($asset));
                    }
                }
            }
        }

        if (isset($assets['css']) && is_array($assets['css'])) {
            foreach ($assets['css'] as $asset) {
                $found = false;
                foreach ($this->assets->getCssAssets() as $assetDto) {
                    if (is_string($asset)) {
                        if ($assetDto->getValue() === $asset) {
                            $found = true;
                        }
                    } elseif ($asset instanceof Asset) {
                        if ($assetDto->getValue() === $asset->getAsDto()->getValue()) {
                            $found = true;
                        }
                    }
                }
                if (!$found) {
                    if ($asset instanceof Asset) {
                        $this->assets->addCssAsset($asset->getAsDto());
                    } elseif (is_string($asset)) {
                        $this->assets->addCssAsset(new AssetDto($asset));
                    }
                }
            }
        }
    }

    public function addWebpackEncoreAsset(AssetDto $assetDto): void
    {
        $this->assets->addWebpackEncoreAsset($assetDto);
    }

    public function addCssAsset(AssetDto $assetDto): void
    {
        $this->assets->addCssAsset($assetDto);
    }

    public function addJsAsset(AssetDto $assetDto): void
    {
        $this->assets->addJsAsset($assetDto);
    }

    public function addHtmlContentToHead(string $htmlContent): void
    {
        $this->assets->addHtmlContentToHead($htmlContent);
    }

    public function addHtmlContentToBody(string $htmlContent): void
    {
        $this->assets->addHtmlContentToBody($htmlContent);
    }

    public function getCustomOptions(): KeyValueStore
    {
        return $this->customOptions;
    }

    public function getCustomOption(string $optionName): mixed
    {
        return $this->customOptions->get($optionName);
    }

    /**
     * @param array<string, mixed> $customOptions
     */
    public function setCustomOptions(array $customOptions): void
    {
        $this->customOptions = KeyValueStore::new($customOptions);
    }

    public function setCustomOption(string $optionName, mixed $optionValue): void
    {
        $this->customOptions->set($optionName, $optionValue);
    }

    public function getDoctrineMetadata(): KeyValueStore
    {
        return $this->doctrineMetadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setDoctrineMetadata(array $metadata): void
    {
        $this->doctrineMetadata = KeyValueStore::new($metadata);
    }

    public function getDisplayedOn(): KeyValueStore
    {
        return $this->displayedOn;
    }

    public function setDisplayedOn(KeyValueStore $displayedOn): void
    {
        $this->displayedOn = $displayedOn;
    }

    public function isDisplayedOn(string $pageName): bool
    {
        return $this->displayedOn->has($pageName);
    }

    public function getConfigurator(): ?FieldConfiguratorInterface
    {
        return $this->configurator;
    }

    public function setConfigurator(?FieldConfiguratorInterface $configurator): void
    {
        $this->configurator = $configurator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
