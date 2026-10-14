<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Services;

use Adeliom\SyliusEasyCrudPlugin\Services\CrudMakerService;
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
}
