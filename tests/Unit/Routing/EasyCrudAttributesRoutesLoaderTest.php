<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Routing;

use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use Adeliom\SyliusEasyCrudPlugin\Routing\EasyCrudAttributesRoutesLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Routing\ResourceLoader;
use Sylius\Bundle\ResourceBundle\Routing\RouteFactory;
use Sylius\Resource\Metadata\Registry;
use Symfony\Component\Routing\RouteCollection;

/**
 * The routes generated from #[AsAdmin], through the `sylius.resource` route loader.
 */
final class EasyCrudAttributesRoutesLoaderTest extends TestCase
{
    #[Test]
    public function the_prefix_of_the_attribute_is_added_to_the_paths_of_the_resource(): void
    {
        $routes = $this->routes();

        self::assertSame('/library/prefixed-books/', $routes->get('app_admin_prefixed_book_index')?->getPath());
        self::assertSame('/library/prefixed-books/{id}/edit', $routes->get('app_admin_prefixed_book_update')?->getPath());
    }

    #[Test]
    public function only_restricts_the_generated_routes(): void
    {
        self::assertSame(
            ['app_admin_only_book_create', 'app_admin_only_book_index'],
            $this->routeNamesOf('app_admin_only_book'),
        );
    }

    #[Test]
    public function except_removes_routes_from_the_generated_ones(): void
    {
        self::assertSame(
            ['app_admin_except_book_create', 'app_admin_except_book_index', 'app_admin_except_book_update'],
            $this->routeNamesOf('app_admin_except_book'),
        );
    }

    /**
     * @return list<string>
     */
    private function routeNamesOf(string $prefix): array
    {
        $names = array_values(array_filter(
            array_keys($this->routes()->all()),
            static fn (string $name): bool => str_starts_with($name, $prefix . '_'),
        ));
        sort($names);

        return $names;
    }

    private function routes(): RouteCollection
    {
        $descriptors = EasyCrudResourceFactory::build([__DIR__ . '/../Metadata/Fixtures/Routing']);

        $registry = new Registry();
        foreach ($descriptors as $descriptor) {
            $registry->addFromAliasAndConfiguration($descriptor['alias'], $descriptor['registry']);
        }

        $loader = new EasyCrudAttributesRoutesLoader(
            array_map(static fn (array $descriptor): array => $descriptor['routing'], $descriptors),
            new ResourceLoader($registry, new RouteFactory()),
        );

        return $loader();
    }
}
