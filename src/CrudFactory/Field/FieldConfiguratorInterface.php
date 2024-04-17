<?php

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Sylius\Component\Resource\Model\ResourceInterface;

interface FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool;

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void;
}
