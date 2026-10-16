<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Resource\Factory\Factory;
use Sylius\Resource\Factory\TranslatableFactory;
use Tests\Adeliom\SyliusEasyCrudPlugin\Admin\PostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\BookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\ComicAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\LegacyBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\MagazineAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\BookRepository;

final class EasyCrudResourceFactoryTest extends TestCase
{
    #[Test]
    public function the_routes_are_not_prefixed_by_default(): void
    {
        $descriptor = $this->descriptor('app.book');

        self::assertSame('', $descriptor['routing']['prefix']);
    }

    #[Test]
    public function the_repository_defaults_to_the_one_of_the_entity_mapping(): void
    {
        $descriptor = $this->descriptor('app.book');

        self::assertSame(BookRepository::class, $descriptor['registry']['classes']['repository']);
        self::assertSame(SyliusCrudResourceController::class, $descriptor['registry']['classes']['controller']);
        self::assertSame(BookAdmin::class, $descriptor['registry']['classes']['form']);
    }

    #[Test]
    public function a_repository_of_the_entity_mapping_that_is_not_a_sylius_repository_is_ignored(): void
    {
        $descriptor = $this->descriptor('library.magazine');

        self::assertArrayNotHasKey('repository', $descriptor['registry']['classes']);
    }

    #[Test]
    public function the_grid_name_is_derived_from_the_alias_and_the_section(): void
    {
        self::assertSame('app_admin_comic_book', ComicAdmin::getName());
        self::assertSame('app_admin_comic_book', $this->descriptor('app.comic_book')['routing']['config']['grid']);
        self::assertSame('library_back_magazine', MagazineAdmin::getName());
        self::assertSame('library_back_magazine', $this->descriptor('library.magazine')['routing']['config']['grid']);
    }

    #[Test]
    public function an_explicit_grid_name_wins(): void
    {
        self::assertSame('app_admin_book', BookAdmin::getName());
    }

    #[Test]
    public function the_derived_vars_are_added_by_default(): void
    {
        $vars = $this->descriptor('app.book')['routing']['config']['vars'];

        self::assertSame('file', $vars['all']['icon']);
        self::assertSame('sylius_easy_crud_plugin.admin.ui.default.index', $vars['index']['header']);
        self::assertSame('update', $vars['update']['redirect']['route']);
    }

    /**
     * Sylius 2 builds the index links of the update page (breadcrumb, back button) with `vars.route.parameters`:
     * the edited id must not leak into them as a query parameter.
     */
    #[Test]
    public function the_derived_update_route_vars_only_keep_the_context(): void
    {
        $vars = $this->descriptor('app.book')['routing']['config']['vars'];

        self::assertSame(['context' => '$context'], $vars['update']['route']['parameters']);
    }

    #[Test]
    public function a_legacy_declaration_can_be_reproduced_exactly(): void
    {
        $descriptor = $this->descriptor('app.legacy_book');

        self::assertSame(EntityRepository::class, $descriptor['registry']['classes']['repository']);
        self::assertSame('symfony', $descriptor['registry']['state_machine_component']);
        self::assertSame([
            'alias' => 'app.legacy_book',
            'section' => 'admin',
            'templates' => '@SyliusEasyCrudPlugin\\crud',
            'redirect' => 'update',
            'grid' => 'app_admin_legacy_book',
            'form' => ['type' => LegacyBookAdmin::class, 'options' => ['context' => '$context']],
            'vars' => ['all' => ['icon' => 'tabler:book', 'subheader' => false]],
            'permission' => true,
            'except' => ['show'],
        ], $descriptor['routing']['config']);
    }

    #[Test]
    public function a_translatable_model_gets_the_translatable_factory_and_a_translation_sub_resource(): void
    {
        $descriptors = EasyCrudResourceFactory::build([\dirname(__DIR__, 2) . '/TestApplication/src/Admin']);
        $registry = $descriptors[0]['registry'];

        self::assertSame(TranslatableFactory::class, $registry['classes']['factory']);
        self::assertSame(PostAdmin::class, $registry['translation']['classes']['form']);
    }

    #[Test]
    public function a_null_resource_value_removes_the_derived_key(): void
    {
        $descriptors = EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/TranslatableAdmin']);
        $registry = $descriptors[0]['registry'];

        self::assertArrayNotHasKey('translation', $registry);
        self::assertSame(Factory::class, $registry['classes']['factory']);
    }

    /**
     * @return array{alias: string, registry: array<string, mixed>, routing: array{prefix: string, config: array<string, mixed>}}
     */
    private function descriptor(string $alias): array
    {
        foreach (EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/Admin']) as $descriptor) {
            if ($alias === $descriptor['alias']) {
                return $descriptor;
            }
        }

        self::fail(sprintf('No descriptor for "%s".', $alias));
    }
}
