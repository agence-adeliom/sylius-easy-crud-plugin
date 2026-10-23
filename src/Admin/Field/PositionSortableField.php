<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Component\Grid\Builder\Field\FieldInterface as GridFieldInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

/**
 * Position of a resource: a drag and drop handle in the list to reorder the rows, saved by
 * {@see \Adeliom\SyliusEasyCrudPlugin\Controller\PositionSortController}:
 *
 * - flat list (default): the resource implements {@see \Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface},
 *   the list is sorted by `position` and may be paginated; in the forms, an optional integer (empty: no position);
 * - tree ({@see self::asTree()}): a Gedmo nested tree, the list is sorted by the tree (left value) and shows all the
 *   siblings on the same page; a row moves among its siblings only, root nodes are not movable; list only, the order
 *   is the tree itself.
 *
 * The handle is the `position-sortable` Stimulus controller of the plugin (assets/package.json).
 */
final class PositionSortableField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_TREE = 'tree';

    public const OPTION_PARENT_PROPERTY = 'parentProperty';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName = 'position', $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(IntegerType::class)
            ->setRequired(false)
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/positionsortable/grid.html.twig')
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/default/show.html.twig')
            ->setSortablePath($propertyName)
            ->setCustomOption(self::OPTION_TREE, false)
            ->setCustomOption(self::OPTION_PARENT_PROPERTY, null)
        ;
    }

    public function asTree(string $parentProperty = 'parent'): self
    {
        return $this
            ->setCustomOption(self::OPTION_TREE, true)
            ->setCustomOption(self::OPTION_PARENT_PROPERTY, $parentProperty)
            ->setSortable(false)
            ->onlyOnIndex()
        ;
    }

    public static function create(string $propertyName, ?string $label = null): GridFieldInterface
    {
        // the whole row: its id, and its parent in a tree
        return TwigField::create($propertyName, '@SyliusEasyCrudPlugin/field/positionsortable/grid.html.twig')->setPath('.');
    }
}
