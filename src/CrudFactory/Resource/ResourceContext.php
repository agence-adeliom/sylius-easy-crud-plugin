<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource;

use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Resource\Metadata\Metadata;

final readonly class ResourceContext
{
    public function __construct(
        private string $alias,
        private Metadata $metadata,
        private RequestConfiguration $requestConfiguration,
    ) {
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getRequestConfiguration(): RequestConfiguration
    {
        return $this->requestConfiguration;
    }
}
