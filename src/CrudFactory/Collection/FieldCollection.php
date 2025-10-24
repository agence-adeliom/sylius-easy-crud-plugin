<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use ArrayIterator;
use Sylius\Resource\Model\ResourceInterface;

/**
 * Initial class from EasyAdmin
 */
final class FieldCollection implements CollectionInterface
{
    /** @var FieldDto[] */
    private array $fields;

    /**
     * @param FieldInterface[] $fields
     */
    private function __construct(
        iterable $fields,
        private FieldConfiguratorCollection $fieldConfiguratorCollection,
        private ?ResourceInterface $resource = null,
    ) {
        $this->fields = $this->processFields($fields);
    }

    public function __clone()
    {
        $clonedFields = [];
        foreach ($this->fields as $fieldDto) {
            $clonedFieldDto = clone $fieldDto;
            $clonedFields[$clonedFieldDto->getUniqueIdAsString()] = $clonedFieldDto;
        }

        $this->fields = $clonedFields;
    }

    /**
     * @param FieldInterface[] $fields
     */
    public static function new(iterable $fields, FieldConfiguratorCollection $fieldConfiguratorCollection, ?ResourceInterface $resource = null): self
    {
        return new self($fields, $fieldConfiguratorCollection, $resource);
    }

    public function get(string $fieldUniqueId): ?FieldDto
    {
        return $this->fields[$fieldUniqueId] ?? null;
    }

    /**
     * It returns the first field associated to the given property or null if none found.
     * Some pages (index/detail) can render the same field more than once.
     * In those cases, this method always returns the first field occurrence.
     */
    public function getByProperty(string $propertyName): ?FieldDto
    {
        foreach ($this->fields as $field) {
            if ($propertyName === $field->getProperty()) {
                return $field;
            }
        }

        return null;
    }

    public function set(FieldDto $newOrUpdatedField): void
    {
        $this->fields[$newOrUpdatedField->getUniqueIdAsString()] = $newOrUpdatedField;
    }

    public function unset(FieldDto $removedField): void
    {
        unset($this->fields[$removedField->getUniqueIdAsString()]);
    }

    public function prepend(FieldDto $newField): void
    {
        $this->fields = array_merge([$newField->getUniqueIdAsString() => $newField], $this->fields);
    }

    public function first(): ?FieldDto
    {
        if (0 === \count($this->fields)) {
            return null;
        }

        return $this->fields[array_key_first($this->fields)];
    }

    public function isEmpty(): bool
    {
        return 0 === \count($this->fields);
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->fields);
    }

    public function offsetGet(mixed $offset): FieldDto
    {
        return $this->fields[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->fields[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->fields[$offset]);
    }

    public function count(): int
    {
        return \count($this->fields);
    }

    /**
     * @return ArrayIterator<int,FieldDto>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->fields);
    }

    /**
     * @param FieldInterface[]|string[] $fields
     *
     * @return FieldDto[]
     */
    private function processFields(iterable $fields): array
    {
        $dtos = [];

        // for DX reasons, fields can be configured as a FieldInterface object and
        // as a simple string with the name of the Doctrine property
        /** @var FieldInterface|string $field */
        foreach ($fields as $field) {
            if (\is_string($field)) {
                $field = Field::new($field);
            }

            $dto = $field->getAsDto();
            if (null === $dto->getFieldFqcn()) {
                $dto->setFieldFqcn($field::class);
            }

            foreach ($this->fieldConfiguratorCollection->getHandlers() as $configurator) {
                if (!$configurator->supports($dto)) {
                    continue;
                }

                $configurator->configure($dto, $this->resource); //, $entityDto, $context
                $dto->setConfigurator($configurator);
            }

            $dtos[$dto->getUniqueIdAsString()] = $dto;
        }

        return $dtos;
    }
}
