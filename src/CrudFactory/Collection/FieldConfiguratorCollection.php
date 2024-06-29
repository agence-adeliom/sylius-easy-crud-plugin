<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;

/**
 * This file allow to put all field configurator services into a collection
 */
class FieldConfiguratorCollection
{
    /**
     * @param iterable<FieldConfiguratorInterface> $handlers
     */
    public function __construct(protected iterable $handlers)
    {}

    /**
     * @return iterable<FieldConfiguratorInterface>
     */
    public function getHandlers(): iterable
    {
        return $this->handlers;
    }
}
