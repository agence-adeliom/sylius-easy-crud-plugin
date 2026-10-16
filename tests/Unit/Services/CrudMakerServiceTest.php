<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Services;

use Adeliom\SyliusEasyCrudPlugin\Services\CrudMakerService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Yaml\Yaml;

final class CrudMakerServiceTest extends TestCase
{
    public function testGeneratedRouteIsPrefixedWithTheSyliusAdminPath(): void
    {
        $generator = $this->createStub(Generator::class);
        $generator->method('getRootDirectory')->willReturn('/srv/project');

        $maker = new CrudMakerService($generator, 'App', null, null, null);

        $routes = Yaml::parse($maker->generateRoute(true, 'App\Entity\Post'));

        self::assertIsArray($routes);
        self::assertArrayHasKey('admin_app_entity_post', $routes);
        self::assertSame('/%sylius_admin.path_name%', $routes['admin_app_entity_post']['prefix']);
    }

    /**
     * Sylius 2 builds the index links of the update page (breadcrumb, back button) with `vars.update.route.parameters`:
     * the edited id must not leak into them as a query parameter.
     */
    #[Test]
    public function generated_update_route_parameters_only_keep_the_context(): void
    {
        $generator = $this->createStub(Generator::class);
        $generator->method('getRootDirectory')->willReturn('/srv/project');

        $maker = new CrudMakerService($generator, 'App', null, null, null);

        $routes = Yaml::parse($maker->generateRoute(true, 'App\Entity\Post'));
        self::assertIsArray($routes);
        $resource = Yaml::parse($routes['admin_app_entity_post']['resource']);
        self::assertIsArray($resource);

        self::assertSame(['context' => '$context'], $resource['vars']['update']['route']['parameters']);
        self::assertSame(['context' => '$context', 'id' => '$id'], $resource['vars']['update']['redirect']['parameters']);
    }
}
