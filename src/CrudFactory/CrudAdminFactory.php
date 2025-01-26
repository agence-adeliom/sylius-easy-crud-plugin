<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldConfiguratorCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CrudAdminFactory
{
    /** @var string[] */
    public array $formThemes = [];

    /** @var AssetDto[] */
    public array $cssAssets = [];

    /** @var AssetDto[] */
    public array $jsAssets = [];

    /** @var AssetDto[] */
    public array $webpackEncoreAssets = [];

    protected ?MenuItem $menu = null;

    /** @var array<array<string, mixed>> */
    public array $columns = [];

    protected ?Metadata $metadata = null;

    protected ?RequestConfiguration $requestConfiguration = null;

    public function __construct(
        protected DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser,
        protected FieldConfiguratorCollection $fieldConfiguratorCollection,
        protected FactoryInterface $menuFactory,
        protected PropertyAccessor $propertyAccessor,
        protected RequestConfigurationFactory $requestConfigurationFactory,
        public RequestStack $requestStack,
        public ParameterBagInterface $parameterBag,
    ) {
        $this->initMenu();
    }

    public function initContext(string $model): void
    {
        try {
            /** @var array<mixed> $resources */
            $resources = $this->parameterBag->get('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            return;
        }

        foreach ($resources as $alias => $configuration) {
            if ($configuration['classes']['model'] === $model) {
                $this->metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);
                $this->requestConfiguration = $this->requestConfigurationFactory
                    ->create(
                        $this->metadata,
                        $this->requestStack->getCurrentRequest(),
                    );
            }
        }
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function getRequestConfiguration(): ?RequestConfiguration
    {
        return $this->requestConfiguration;
    }

    public function getFieldConfiguratorCollection(): FieldConfiguratorCollection
    {
        return $this->fieldConfiguratorCollection;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    public function getDoctrineOrmTypeGuesser(): DoctrineOrmTypeGuesser
    {
        return $this->doctrineOrmTypeGuesser;
    }

    public function initMenu(): void
    {
        $this->menu = new MenuItem('root', $this->menuFactory);
    }

    public function getMenu(): MenuItem
    {
        return $this->menu;
    }

    public function getMenuFactory(): FactoryInterface
    {
        return $this->menuFactory;
    }

    public function manageFieldAssets(FieldDto $fieldDto): void
    {
        if (is_array($fieldDto->getFormThemes())) {
            foreach ($fieldDto->getFormThemes() as $theme) {
                $this->formThemes[$theme] = $theme;
            }
        }

        if (is_array($fieldDto->getAssets()->getCssAssets())) {
            foreach ($fieldDto->getAssets()->getCssAssets() as $path) {
                $this->cssAssets[$path->getValue()] = $path;
            }
        }

        if (is_array($fieldDto->getAssets()->getJsAssets())) {
            foreach ($fieldDto->getAssets()->getJsAssets() as $path) {
                $this->jsAssets[$path->getValue()] = $path;
            }
        }

        if (is_array($fieldDto->getAssets()->getWebpackEncoreAssets())) {
            foreach ($fieldDto->getAssets()->getWebpackEncoreAssets() as $path) {
                $this->webpackEncoreAssets[$path->getValue()] = $path;
            }
        }
    }

    /**
     * @return array{MenuItem, array<string, mixed>}
     */
    public function addTab(string $name, ?string $label = null, ?string $template = null): array
    {
        $menuItem = new MenuItem($name, $this->getMenuFactory());
        $menuItem->setAttribute(
            'template',
            null === $template ?
                '@SyliusEasyCrudPlugin/crud/form/_tab.html.twig' :
                $template,
        );
        $menuItem->setLabel($label);
        $this->getMenu()->addChild($menuItem);
        $column = $this->newColumn($menuItem, 'default_column', null);
        $this->columns[] = $column;

        return [$menuItem, $column];
    }

    /**
     * @return array<string, mixed>
     */
    public function addColumn(MenuItem $menuItem, ?FieldDto $fieldDto): array
    {
        $column = $this->newColumn(
            $menuItem,
            $fieldDto->getProperty(),
            $fieldDto->getLabel(),
            $fieldDto->getCustomOption('columnSize'),
            $fieldDto->getCustomOption('newLine'),
        );
        $this->columns[] = $column;

        return $column;
    }

    /**
     * @return array<string, mixed>
     */
    private function newColumn(
        MenuItem $menuItem,
        ?string $name = null,
        ?string $label = null,
        ?ColumnSizeEnum $size = ColumnSizeEnum::WIDE_16_OF_16,
        ?bool $newLine = true,
    ): array {
        return [
            'id' => md5((string) rand()),
            'name' => $name ?? 'default_column',
            'size' => $size,
            'label' => $label ?? null,
            'newLine' => $newLine,
            'menuItem' => $menuItem->getName(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewVars(): array
    {
        $vars = [];
        $vars['form_themes'] = array_values(
            [
                '@SyliusAdmin/Form/theme.html.twig',
            ]
            + $this->formThemes,
        );
        $vars['css_assets'] = $this->cssAssets;
        $vars['js_assets'] = $this->jsAssets;
        $vars['webpack_encore_assets'] = $this->webpackEncoreAssets;
        $vars['menu'] = $this->getMenu();
        $vars['columns'] = count($this->columns) ? $this->columns : [[
           'id' => md5((string) rand()),
           'name' => 'default',
           'label' => null,
           'size' => ColumnSizeEnum::WIDE_16_OF_16,
           'menuItem' => $this->menu->count() ?
               $this->menu->getFirstChild() :
               null,
       ]];

        return $vars;
    }
}
