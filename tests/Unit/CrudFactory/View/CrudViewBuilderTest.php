<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CrudFactory\View;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\AssetDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\View\CrudViewBuilder;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;

final class CrudViewBuilderTest extends TestCase
{
    public function testBuildersDoNotShareAssetsMenuOrColumns(): void
    {
        $first = $this->newBuilder();
        $first->manageFieldAssets($this->fieldWithAssets('title', '/admin/first.css'));
        $first->addTab('first', 'First');

        $second = $this->newBuilder();
        $secondViewVars = $second->build()->toViewVars();

        $this->assertSame([], $secondViewVars['css_assets']);
        $this->assertSame(0, $secondViewVars['menu']->count());
        $this->assertSame('default', $secondViewVars['columns'][0]['name']);
    }

    public function testAssetsAndFormThemesAreDeduplicatedByKey(): void
    {
        $builder = $this->newBuilder();
        $builder->manageFieldAssets($this->fieldWithAssets('title', '/admin/editor.css'));
        $builder->manageFieldAssets($this->fieldWithAssets('body', '/admin/editor.css'));

        $viewVars = $builder->build()->toViewVars();

        $this->assertSame([
            '@SyliusAdmin/shared/form_theme.html.twig',
            '@App/form/theme.html.twig',
        ], $viewVars['form_themes']);
        $this->assertSame(['/admin/editor.css'], array_keys($viewVars['css_assets']));
        $this->assertSame(['/admin/editor.js'], array_keys($viewVars['js_assets']));
        $this->assertSame(['admin_editor'], array_keys($viewVars['webpack_encore_assets']));
    }

    public function testTabsAndColumnsBuildExpectedViewVariables(): void
    {
        $builder = $this->newBuilder();

        [$menuItem, $tabColumn] = $builder->addTab('content', 'Content', horizontalDisplay: true);
        $column = $builder->addColumn($menuItem, $this->field('title', 'Title'));

        $viewVars = $builder->build()->toViewVars();

        $this->assertSame('content', $viewVars['menu']->getFirstChild()->getName());
        $this->assertTrue($viewVars['menu']->getAttribute(TabField::HORIZONTAL_DISPLAY));
        $this->assertSame('default_column', $tabColumn['name']);
        $this->assertSame('title', $column['name']);
        $this->assertSame([$tabColumn, $column], $viewVars['columns']);
    }

    public function testDefaultColumnIsProvidedWhenNoColumnWasAdded(): void
    {
        $viewVars = $this->newBuilder()->build()->toViewVars();

        $this->assertSame('default', $viewVars['columns'][0]['name']);
        $this->assertNull($viewVars['columns'][0]['menuItem']);
    }

    private function newBuilder(): CrudViewBuilder
    {
        return new CrudViewBuilder(new MenuFactory());
    }

    private function field(string $property, ?string $label = null): FieldDto
    {
        $field = new FieldDto();
        $field->setProperty($property);
        $field->setLabel($label);

        return $field;
    }

    private function fieldWithAssets(string $property, string $cssPath): FieldDto
    {
        $field = $this->field($property);
        $field->addFormTheme('@App/form/theme.html.twig');
        $field->addCssAsset(new AssetDto($cssPath));
        $field->addJsAsset(new AssetDto('/admin/editor.js'));
        $field->addWebpackEncoreAsset(new AssetDto('admin_editor'));

        return $field;
    }
}
