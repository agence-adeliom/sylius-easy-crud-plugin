<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Integration\Routing;

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
    }
}
