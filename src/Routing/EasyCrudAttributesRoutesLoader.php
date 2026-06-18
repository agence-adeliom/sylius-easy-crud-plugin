<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Routing;

use Sylius\Bundle\ResourceBundle\Routing\ResourceLoader;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

/**
 * Replays the routing configs produced from #[AsEasyCrudAdmin] attributes
 * (precomputed in the bundle extension's prepend()) through the legacy
 * `sylius.resource` route loader, so the generated routes are byte-identical to
 * the ones the equivalent `type: sylius.resource` block used to produce.
 *
 * Mirrors Sylius' own CrudRoutesAttributesLoader. Tagged `routing.route_loader`,
 * it is invoked automatically by the framework — no routing import is required.
 */
final class EasyCrudAttributesRoutesLoader implements RouteLoaderInterface
{
    /**
     * @param array<int, array{prefix: string, config: array<string, mixed>}> $routingConfigs
     */
    public function __construct(
        private array $routingConfigs,
        private ResourceLoader $resourceLoader,
    ) {
    }

    public function __invoke(): RouteCollection
    {
        $routeCollection = new RouteCollection();

        foreach ($this->routingConfigs as $routing) {
            $resourceRoutes = $this->resourceLoader->load(Yaml::dump($routing['config']));

            $prefix = trim($routing['prefix'], '/');
            if ('' !== $prefix) {
                $resourceRoutes->addPrefix('/' . $prefix);
            }

            $routeCollection->addCollection($resourceRoutes);
        }

        return $routeCollection;
    }
}
