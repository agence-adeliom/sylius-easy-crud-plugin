<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\View;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;

final class CrudViewBuilder
{
    /** @var string[] */
    private array $formThemes = [];

    /** @var AssetDto[] */
    private array $cssAssets = [];

    /** @var AssetDto[] */
    private array $jsAssets = [];

    /** @var AssetDto[] */
    private array $webpackEncoreAssets = [];

    private MenuItem $menu;

    /** @var array<array<string, mixed>> */
    private array $columns = [];

    public function __construct(
        private readonly FactoryInterface $menuFactory,
    ) {
        $this->menu = new MenuItem('root', $this->menuFactory);
    }

    public function manageFieldAssets(FieldDto $fieldDto): void
    {
        foreach ($fieldDto->getFormThemes() as $theme) {
            $this->formThemes[$theme] = $theme;
        }

        foreach ($fieldDto->getAssets()->getCssAssets() as $path) {
            $this->cssAssets[$path->getValue()] = $path;
        }

        foreach ($fieldDto->getAssets()->getJsAssets() as $path) {
            $this->jsAssets[$path->getValue()] = $path;
        }

        foreach ($fieldDto->getAssets()->getWebpackEncoreAssets() as $path) {
            $this->webpackEncoreAssets[$path->getValue()] = $path;
        }
    }

    /**
     * @return array{MenuItem, array<string, mixed>}
     */
    public function addTab(
        string $name,
        ?string $label = null,
        ?string $template = null,
        ?bool $horizontalDisplay = false,
    ): array {
        $menuItem = new MenuItem($name, $this->menuFactory);
        $menuItem->setAttribute(
            'template',
            $template ?? '@SyliusEasyCrudPlugin/crud/form/_tab.html.twig',
        );
        $menuItem->setLabel($label);
        $this->menu->addChild($menuItem);

        if (null === $this->menu->getAttribute(TabField::HORIZONTAL_DISPLAY)) {
            $this->menu->setAttribute(TabField::HORIZONTAL_DISPLAY, $horizontalDisplay);
        }

        $column = $this->newColumn($menuItem, 'default_column', null);
        $this->columns[] = $column;

        return [$menuItem, $column];
    }

    /**
     * @return array<string, mixed>
     */
    public function addColumn(MenuItem $menuItem, FieldDto $fieldDto): array
    {
        /** @var ColumnSizeEnum|null $size */
        $size = $fieldDto->getCustomOption('columnSize');

        /** @var bool|null $newLine */
        $newLine = $fieldDto->getCustomOption('newLine');

        $column = $this->newColumn(
            $menuItem,
            $fieldDto->getProperty(),
            $fieldDto->getLabel(),
            $size,
            $newLine,
        );
        $this->columns[] = $column;

        return $column;
    }

    public function build(): CrudView
    {
        return new CrudView(
            array_values([
                '@SyliusAdmin/shared/form_theme.html.twig',
            ] + $this->formThemes),
            $this->cssAssets,
            $this->jsAssets,
            $this->webpackEncoreAssets,
            $this->menu,
            count($this->columns) ? $this->columns : [[
                'id' => md5((string) rand()),
                'name' => 'default',
                'label' => null,
                'size' => ColumnSizeEnum::WIDE_12_OF_12,
                'menuItem' => $this->menu->count() ? $this->menu->getFirstChild() : null,
            ]],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function newColumn(
        MenuItem $menuItem,
        ?string $name = null,
        ?string $label = null,
        ?ColumnSizeEnum $size = ColumnSizeEnum::WIDE_12_OF_12,
        ?bool $newLine = true,
    ): array {
        return [
            'id' => md5((string) rand()),
            'name' => $name ?? 'default_column',
            'size' => $size,
            'label' => $label,
            'newLine' => $newLine,
            'menuItem' => $menuItem->getName(),
        ];
    }
}
