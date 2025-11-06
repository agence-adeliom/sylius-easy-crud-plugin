<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Sylius\Bundle\GridBundle\Builder\Action\ActionInterface;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\SubItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\Filter\FilterInterface;
use Sylius\Bundle\GridBundle\Builder\GridBuilder;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Resource\Model\TranslatableInterface;

abstract class AbstractGridType extends AbstractResourceType implements ResourceAwareGridInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $gridBuilder = $this->createGridBuilder();

        $this->buildGrid($gridBuilder);

        return $gridBuilder->toArray();
    }

    private function createGridBuilder(): GridBuilderInterface
    {
        $resourceClass = $this->getResourceClass();
        $grid = GridBuilder::create($this::getName(), $resourceClass);

        if ($this instanceof AbstractAdmin) {
            if (method_exists($this, 'getDefaultSortColumn')) {
                if ('' !== $this::getDefaultSortColumn()) {
                    $grid->orderBy($this::getDefaultSortColumn(), $this::getDefaultSortOrder());
                }
            }
            if (is_array(class_implements($resourceClass)) && in_array(TranslatableInterface::class, class_implements($resourceClass))) {
                if (method_exists($this, 'getRepositoryMethod')) {
                    $grid->setDriverOption('repository', $this::getRepositoryMethod());
                }
            }
            if (method_exists($this, 'getLimits')) {
                $grid->setLimits($this::getLimits());
            }
        }

        return $grid;
    }

    /**
     * @return array<string, array<ActionInterface>>
     */
    protected function processActions(string $pageName): array
    {
        $pageActions = [
            'main' => [],
            'bulk' => [],
            'item' => [],
            'subitem' => [],
        ];
        if ($this instanceof AbstractAdmin) {
            $actions = $this->configureActions(
                $pageName,
            );
            $actionsDto = $actions->getAsDto($pageName);

            $pageActions = [
                'main' => $actionsDto->getGridActionsByType(Action::TYPE_GLOBAL),
                'bulk' => $actionsDto->getGridActionsByType(Action::TYPE_BATCH),
                'item' => $actionsDto->getGridActionsByType(Action::TYPE_ITEM),
                'subitem' => $actionsDto->getGridActionsByType(Action::TYPE_SUB_ITEM),
            ];
        }

        return $pageActions;
    }

    /**
     * @param array{
     *     type: string,
     *     options?: array<string, mixed>,
     *     icon?: string,
     *     enabled?: bool,
     *     position?: int,
     *     label?: string
     * } $data
     */
    protected function transformActionsAsGridDefinition(string $name, array $data): \Sylius\Component\Grid\Definition\Action
    {
        $action = \Sylius\Component\Grid\Definition\Action::fromNameAndType($name, $data['type']);
        $action->setOptions($data['options'] ?? []);
        $action->setIcon($data['icon'] ?? '');
        $action->setEnabled($data['enabled'] ?? true);
        $action->setPosition($data['position'] ?? 1);
        $action->setLabel($data['label'] ?? '');

        return $action;
    }

    /**
     * @return array<string, array<int, \Sylius\Component\Grid\Definition\Action>>
     */
    protected function processDetailAndUpdateActions(string $pageName): array
    {
        $pageActions = $this->processActions($pageName);
        $mainActions = MainActionGroup::create(...$pageActions[Action::TYPE_GLOBAL]);
        $itemActions = ItemActionGroup::create(...$pageActions[Action::TYPE_ITEM]);
        $subItemActions = ItemActionGroup::create(...$pageActions[Action::TYPE_SUB_ITEM]);
        $actions = [
            'main' => [],
            'item' => [],
            'subitem' => [],
        ];
        foreach ($mainActions->toArray() as $name => $mainAction) {
            $actions['main'][] = $this->transformActionsAsGridDefinition($name, $mainAction);
        }
        foreach ($itemActions->toArray() as $name => $itemAction) {
            $actions['item'][] = $this->transformActionsAsGridDefinition($name, $itemAction);
        }
        foreach ($subItemActions->toArray() as $name => $subItemAction) {
            $actions['subitem'][] = $this->transformActionsAsGridDefinition($name, $subItemAction);
        }

        return $actions;
    }

    /**
     * @return array<string, array<ActionInterface>>
     */
    protected function processGridActions(
        ?GridBuilderInterface $gridBuilder = null,
    ): array {
        $pageActions = $this->processActions(Crud::PAGE_INDEX);

        if ($gridBuilder instanceof GridBuilderInterface) {
            $gridBuilder
                ->addActionGroup(
                    MainActionGroup::create(...$pageActions[Action::TYPE_GLOBAL]),
                )
                ->addActionGroup(
                    BulkActionGroup::create(...$pageActions[Action::TYPE_BATCH]),
                )
                ->addActionGroup(
                    ItemActionGroup::create(...$pageActions[Action::TYPE_ITEM]),
                )
                ->addActionGroup(
                    SubItemActionGroup::create(...$pageActions[Action::TYPE_SUB_ITEM]),
                );
        }

        return $pageActions;
    }

    protected function processGridFilters(
        ?GridBuilderInterface $gridBuilder = null,
    ): void {
        if ($this instanceof AbstractAdmin) {
            $filters = $this->configureFilters();

            if ($gridBuilder instanceof GridBuilderInterface) {
                foreach ($filters as $filter) {
                    if ($filter instanceof FilterInterface) {
                        $gridBuilder->addFilter($filter);
                    }
                }
            }
        }
    }

    protected function processGridDefaultSort(
        ?GridBuilderInterface $gridBuilder = null,
    ): void {
        if ($this instanceof AbstractAdmin && null !== $gridBuilder) {
            foreach ($this->configureDefaultSort() as $name => $direction) {
                $gridBuilder->addOrderBy($name, $direction);
            }
        }
    }
}
