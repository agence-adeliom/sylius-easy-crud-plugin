<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;

interface FieldInterface
{
    public static function new(string $propertyName, ?string /* TranslatableInterface|string|false|null */ $label = null): self;

    public function getAsDto(): FieldDto;
}
