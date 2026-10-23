<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\PositionSortableField;
use Symfony\Component\HttpFoundation\Response;

trigger_deprecation('agence-adeliom/sylius-easy-crud-plugin', '2.3.3', 'The "%s" trait is deprecated and will be removed in the next minor version, use "%s::new()->asTree()" instead.', PositionSortableActionTrait::class, PositionSortableField::class);

/**
 * @deprecated since 2.3.3, removed in the next minor version: use PositionSortableField::new()->asTree(), saved by the
 *             plugin's own controller. Its action depended on CrudFactory\Context\AdminContext, a class the plugin no
 *             longer has, so it could not work; kept so that the classes using it still load.
 */
trait PositionSortableActionTrait
{
    public function sortPositionAction(mixed ...$arguments): Response
    {
        throw new \LogicException(sprintf('%s::sortPositionAction() no longer works: use %s::new()->asTree(), saved by the plugin\'s own controller.', PositionSortableActionTrait::class, PositionSortableField::class));
    }
}
