<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection;

/**
 * This file allow to put all field configurator services into a collection
 */

class FieldConfiguratorCollection
{
    private array $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers =
            $handlers instanceof \Traversable
                ? iterator_to_array($handlers)
                : $handlers;
    }

    public function getHandlers(): iterable
    {
        return $this->handlers;
    }

    /**
     * @throws \ReflectionException
     */
    public function get($name): mixed
    {
        foreach ($this->handlers as $service) {
            return $service;
        }

        return null;
    }
}
