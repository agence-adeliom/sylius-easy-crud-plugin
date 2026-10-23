<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Collection whose order is not edited: a {@see SortableCollectionField} without the drag and drop (same form type,
 * same accordion entries, same options). The submitted array is re-indexed as a list; to keep the keys, set the
 * `keep_as_list` form type option to false.
 */
final class CollectionField implements FieldInterface
{
    use CollectionFieldTrait;
    use FieldTrait;

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(SortableCollectionType::class)
            ->addAssets(SortableCollectionType::configureAdminAssets())
            ->addFormThemes(SortableCollectionType::configureAdminFormThemes())
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/sortablecollection/show.html.twig')
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/sortablecollection/grid.html.twig')
            ->addCssClass('field-collection')
            ->setCustomOption(SortableCollectionField::OPTION_ALLOW_DRAG, false)
            ->setCustomOption(SortableCollectionField::OPTION_ALLOW_ADD, true)
            ->setCustomOption(SortableCollectionField::OPTION_ALLOW_DELETE, true)
            ->setCustomOption(SortableCollectionField::OPTION_ENTRY_IS_COMPLEX, null)
            ->setCustomOption(SortableCollectionField::OPTION_ENTRY_TYPE, TextType::class)
            ->setCustomOption(SortableCollectionField::OPTION_SHOW_ENTRY_LABEL, false)
            ->setCustomOption(SortableCollectionField::OPTION_RENDER_EXPANDED, false);
    }
}
