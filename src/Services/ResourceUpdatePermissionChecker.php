<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Resource\Metadata\MetadataInterface;
use Sylius\Resource\ResourceActions;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * The permission of the update route of a resource, checked outside of that route (the drag and drop of the
 * PositionSortableField modifies the resource): the `_sylius` configuration of the route (YAML resource block or
 * #[AsAdmin]) is read back and checked like `ResourceController::isGrantedOr403()` does, with the authorization checker
 * of the resource controllers. A resource without update route needs no permission.
 */
final class ResourceUpdatePermissionChecker
{
    /** @var array<string, array<mixed>|null> `_sylius` of the update route, by controller */
    private array $updateRoutes = [];

    public function __construct(
        private readonly RouterInterface $router,
        #[Autowire(service: 'sylius.resource_controller.request_configuration_factory')]
        private readonly RequestConfigurationFactoryInterface $requestConfigurationFactory,
        #[Autowire(service: 'sylius.resource_controller.authorization_checker')]
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function denyUnlessGranted(MetadataInterface $metadata, Request $request): void
    {
        $syliusConfiguration = $this->updateRouteConfiguration($metadata);
        if (null === $syliusConfiguration) {
            return;
        }

        $configuration = $this->requestConfigurationFactory->create($metadata, $request->duplicate(attributes: ['_sylius' => $syliusConfiguration]));
        if ($configuration->hasPermission() && !$this->authorizationChecker->isGranted($configuration, $configuration->getPermission(ResourceActions::UPDATE))) {
            throw new AccessDeniedHttpException(sprintf('The permission to update the "%s" resource is required.', $metadata->getAlias()));
        }
    }

    /**
     * @return array<mixed>|null
     */
    private function updateRouteConfiguration(MetadataInterface $metadata): ?array
    {
        $controller = $metadata->getServiceId('controller') . '::updateAction';
        if (\array_key_exists($controller, $this->updateRoutes)) {
            return $this->updateRoutes[$controller];
        }

        // the admin route first, when the resource has several update routes (admin, API)
        $found = null;
        foreach ($this->router->getRouteCollection() as $route) {
            $syliusConfiguration = $route->getDefault('_sylius');
            if ($controller !== $route->getDefault('_controller') || !\is_array($syliusConfiguration)) {
                continue;
            }
            if ('admin' === ($syliusConfiguration['section'] ?? null)) {
                $found = $syliusConfiguration;

                break;
            }
            $found ??= $syliusConfiguration;
        }

        return $this->updateRoutes[$controller] = $found;
    }
}
