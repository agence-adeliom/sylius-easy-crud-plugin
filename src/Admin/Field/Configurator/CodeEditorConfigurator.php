<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CodeEditorField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

final class CodeEditorConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return CodeEditorField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null, ?string $pageName = null): void
    {
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
