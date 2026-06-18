<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ResourceContextResolver
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private RequestStack $requestStack,
        private RequestConfigurationFactory $requestConfigurationFactory,
    ) {
    }

    public function resolve(string $model): ?ResourceContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        try {
            /** @var mixed $resources */
            $resources = $this->parameterBag->get('sylius.resources');
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!is_array($resources)) {
            return null;
        }

        foreach ($resources as $alias => $configuration) {
            if (!is_string($alias) || !is_array($configuration)) {
                continue;
            }

            $classes = $configuration['classes'] ?? null;
            if (!is_array($classes) || ($classes['model'] ?? null) !== $model) {
                continue;
            }

            $metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);

            return new ResourceContext(
                $alias,
                $metadata,
                $this->requestConfigurationFactory->create($metadata, $request),
            );
        }

        return null;
    }
}
