<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\TextEditorType;

final class TextEditorField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TextEditorType::class)
            ->addFormThemes(TextEditorType::configureAdminFormThemes())
            ->addJsFiles(TextEditorType::configureAdminAssets()['js'])
            ->addCssFiles(TextEditorType::configureAdminAssets()['css'])
        ;
    }
}
