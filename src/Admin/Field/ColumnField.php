<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

final class ColumnField implements FieldInterface
{
    use FieldTrait;

    public static function new(?string $label = null, $fieldsConfig = []): self
    {
        return (new self())
            ->setProperty('_' . rand(0, 1000))
            ->setLabel($label)
            ->setFormType(HiddenType::class)
            ->hideOnIndex()
            ->setFormTypeOption('mapped', false)
            ->setCustomOption('newLine', false)
            ->setCustomOption('columnSize', ColumnSizeEnum::WIDE_16_OF_16)
        ;
    }

    public function setSize(ColumnSizeEnum $size): self
    {
        $this->setCustomOption('columnSize', $size);

        return $this;
    }

    public function newLine(): self
    {
        $this->setCustomOption('newLine', true);

        return $this;
    }
}
