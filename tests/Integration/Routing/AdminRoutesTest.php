<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Integration\Routing;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AdminRoutesTest extends KernelTestCase
{
    /**
     * The routes imported through `@SyliusEasyCrudPlugin/config/routes.yaml`
     * follow the Sylius admin path instead of a hard-coded `/admin`.
     */
    public function testPluginRoutesAreServedUnderTheSyliusAdminPath(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $adminPath = $container->getParameter('sylius_admin.path_name');
        self::assertIsString($adminPath);

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertSame(
            sprintf('/%s/oembed/', $adminPath),
            $router->generate('sylius_easy_crud.get_oembed'),
        );
        self::assertSame(
            sprintf('/%s/autocomplete/', $adminPath),
            $router->generate('sylius_admin_ajax_resources'),
        );
        self::assertSame(
            sprintf('/%s/position-sort/app.book', $adminPath),
            $router->generate('sylius_easy_crud_admin_position_sort', ['resource' => 'app.book']),
        );
        self::assertSame(
            sprintf('/%s/position-sort/app.book/tree', $adminPath),
            $router->generate('sylius_easy_crud_admin_position_sort_tree', ['resource' => 'app.book']),
        );
    }

    /**
     * The routes of the resources declared with #[AsAdmin] are imported by
     * `@SyliusEasyCrudPlugin/config/routes.yaml` under the Sylius admin path too.
     */
    #[Test]
    public function attribute_resource_routes_are_served_under_the_sylius_admin_path(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $adminPath = $container->getParameter('sylius_admin.path_name');
        self::assertIsString($adminPath);

        $router = $container->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertSame(
            sprintf('/%s/tests-adeliom-sylius-easy-crud-plugin-entity-posts/', $adminPath),
            $router->generate('tests_adeliom_sylius_easy_crud_plugin_admin_tests_adeliom_sylius_easy_crud_plugin_entity_post_index'),
        );
    }
}
