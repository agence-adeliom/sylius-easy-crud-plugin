<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldConfiguratorCollection;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactory;
use Sylius\Resource\Metadata\Metadata;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CrudAdminFactory
{
    protected ?Metadata $metadata = null;

    protected ?RequestConfiguration $requestConfiguration = null;

    public function __construct(
        protected DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser,
        protected FieldConfiguratorCollection $fieldConfiguratorCollection,
        protected PropertyAccessor $propertyAccessor,
        protected RequestConfigurationFactory $requestConfigurationFactory,
        public RequestStack $requestStack,
        public ParameterBagInterface $parameterBag,
    ) {
    }

    public function initContext(string $model): ?string
    {
        try {
            /** @var array<mixed> $resources */
            $resources = $this->parameterBag->get('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        foreach ($resources as $alias => $configuration) {
            if (
                $this->requestStack->getCurrentRequest() &&
                is_array($configuration) &&
                is_array($configuration['classes']) &&
                $configuration['classes']['model'] === $model
            ) {
                $this->metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);
                $this->requestConfiguration = $this->requestConfigurationFactory
                    ->create(
                        $this->metadata,
                        $this->requestStack->getCurrentRequest(),
                    );

                return $alias;
            }
        }

        return null;
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function getRequestConfiguration(): ?RequestConfiguration
    {
        return $this->requestConfiguration;
    }

    public function getFieldConfiguratorCollection(): FieldConfiguratorCollection
    {
        return $this->fieldConfiguratorCollection;
    }

    public function getPropertyAccessor(): PropertyAccessor
    {
        return $this->propertyAccessor;
    }

    public function getDoctrineOrmTypeGuesser(): DoctrineOrmTypeGuesser
    {
        return $this->doctrineOrmTypeGuesser;
    }
}
