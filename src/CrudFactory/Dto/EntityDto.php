<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
final class EntityDto
{
    public function getInstance(): ?ResourceInterface
    {
        return null;
    }
}
