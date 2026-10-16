<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ResourceContextResolver
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private RequestStack $requestStack,
        private RequestConfigurationFactory $requestConfigurationFactory,
    ) {
    }

    /**
     * @param string            $model      the model of the Admin
     * @param class-string|null $adminClass the Admin, to pick its own resource when several resources share the model
     */
    public function resolve(string $model, ?string $adminClass = null): ?ResourceContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        try {
            $resources = $this->parameterBag->get('sylius.resources');
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!is_array($resources)) {
            return null;
        }

        $fallback = null;
        foreach ($resources as $alias => $configuration) {
            if (!is_string($alias) || !is_array($configuration)) {
                continue;
            }

            $classes = $configuration['classes'] ?? null;
            if (!is_array($classes) || ($classes['model'] ?? null) !== $model) {
                continue;
            }

            // Several Admins may share a model, each with its own resource: the one whose form is the Admin wins.
            if (null !== $adminClass && ($classes['form'] ?? null) === $adminClass) {
                return $this->createContext($alias, $configuration, $request);
            }

            $fallback ??= [$alias, $configuration];
        }

        return null === $fallback ? null : $this->createContext($fallback[0], $fallback[1], $request);
    }

    /**
     * @param array<mixed> $configuration
     */
    private function createContext(string $alias, array $configuration, Request $request): ResourceContext
    {
        $metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);

        return new ResourceContext(
            $alias,
            $metadata,
            $this->requestConfigurationFactory->create($metadata, $request),
        );
    }
}
