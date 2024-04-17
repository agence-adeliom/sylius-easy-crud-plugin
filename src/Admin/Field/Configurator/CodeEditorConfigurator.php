<?php

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CodeEditorField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;

use Sylius\Component\Resource\Model\ResourceInterface;

final class CodeEditorConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return CodeEditorField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null, ?string $pageName = null): void
    {}
}
