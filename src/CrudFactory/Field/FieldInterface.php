<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
interface FieldInterface
{
    public static function new(string $propertyName, ?string /* TranslatableInterface|string|false|null */ $label = null);

    public function getAsDto(): FieldDto;
}
