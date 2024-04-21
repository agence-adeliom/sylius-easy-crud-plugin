<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field;

use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
final class Field implements FieldInterface
{
    use FieldTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label);
    }
}
