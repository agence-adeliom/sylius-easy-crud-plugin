<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto;

use Sylius\Resource\Model\ResourceInterface;

final class EntityDto
{
    public function __construct(
        private readonly ?ResourceInterface $instance = null,
    ) {
    }

    public function getInstance(): ?ResourceInterface
    {
        return $this->instance;
    }
}
