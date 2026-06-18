<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\View;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Knp\Menu\MenuItem;

final readonly class CrudView
{
    /**
     * @param string[] $formThemes
     * @param AssetDto[] $cssAssets
     * @param AssetDto[] $jsAssets
     * @param AssetDto[] $webpackEncoreAssets
     * @param array<array<string, mixed>> $columns
     */
    public function __construct(
        private array $formThemes,
        private array $cssAssets,
        private array $jsAssets,
        private array $webpackEncoreAssets,
        private MenuItem $menu,
        private array $columns,
    ) {
    }

    /**
     * @return AssetDto[]
     */
    public function getCssAssets(): array
    {
        return $this->cssAssets;
    }

    /**
     * @return AssetDto[]
     */
    public function getJsAssets(): array
    {
        return $this->jsAssets;
    }

    /**
     * @return AssetDto[]
     */
    public function getWebpackEncoreAssets(): array
    {
        return $this->webpackEncoreAssets;
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewVars(): array
    {
        return [
            'form_themes' => $this->formThemes,
            'css_assets' => $this->cssAssets,
            'js_assets' => $this->jsAssets,
            'webpack_encore_assets' => $this->webpackEncoreAssets,
            'menu' => $this->menu,
            'columns' => $this->columns,
        ];
    }
}
