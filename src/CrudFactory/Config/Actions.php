<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\ActionConfigDto;
use Sylius\Bundle\GridBundle\Builder\Action\Action as SyliusAction;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\ShowAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Resource\Metadata\Metadata;
use function Symfony\Component\Translation\t;

final class Actions
{
    private ActionConfigDto $dto;

    private ?Metadata $metadata = null;

    private ?RequestConfiguration $requestConfiguration = null;

    private function __construct(ActionConfigDto $actionConfigDto)
    {
        $this->dto = $actionConfigDto;
    }

    public static function new(): self
    {
        $dto = new ActionConfigDto();

        return new self($dto);
    }

    public function setRoutePrefix(string $routePrefix): self
    {
        $this->dto->setRoutePrefix($routePrefix);

        return $this;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->dto->getRoutePrefix();
    }

    public function addItemAction(string $pageName, Action|string $actionNameOrObject): self
    {
        return $this->doAddAction($pageName, $actionNameOrObject);
    }

    public function addGlobalAction(string $pageName, Action|string $actionNameOrObject): self
    {
        return $this->doAddAction($pageName, $actionNameOrObject, false, true);
    }

    public function addBatchAction(Action|string $actionNameOrObject): self
    {
        return $this->doAddAction(Crud::PAGE_INDEX, $actionNameOrObject, true);
    }

    public function set(string $pageName, Action|string $actionNameOrObject): self
    {
        $action = \is_string($actionNameOrObject) ? $this->createBuiltInAction($pageName, $actionNameOrObject) : $actionNameOrObject;

        $this->dto->appendAction($pageName, $action->getAsDto());

        return $this;
    }

    public function update(string $pageName, string $actionName, callable $callable): self
    {
        if (null === $actionDto = $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot update it. Instead, add the action with the "add()" method.', $actionName, $pageName));
        }

        $action = $actionDto->getAsConfigObject();
        /** @var Action $action */
        $action = $callable($action);
        $this->dto->setAction($pageName, $action->getAsDto());

        return $this;
    }

    public function remove(string $pageName, string $actionName): self
    {
        if (null === $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot remove it.', $actionName, $pageName));
        }

        $this->dto->removeAction($pageName, $actionName);
        // if 'delete' is removed, 'batch delete' is removed automatically (but the
        // opposite doesn't happen). This is the most common case, but user can re-add
        // the 'batch delete' action if needed manually
        if (Action::DELETE === $actionName) {
            $this->dto->removeAction($pageName, Action::BATCH_DELETE);
        }

        return $this;
    }

    /**
     * @param string[] $orderedActionNames
     */
    public function reorder(string $pageName, array $orderedActionNames): self
    {
        $newActionOrder = [];
        $currentActions = $this->dto->getAllActions();
        foreach ($orderedActionNames as $actionName) {
            if (!\array_key_exists($actionName, $currentActions[$pageName])) {
                throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot set its order.', $actionName, $pageName));
            }

            $newActionOrder[] = $actionName;
        }

        // add the remaining actions that weren't ordered explicitly. This allows
        // user to only configure the actions they want to see first and rely on the
        // existing order for the rest of actions
        foreach ($currentActions[$pageName] as $actionName => $action) {
            if (!\in_array($actionName, $newActionOrder, true)) {
                $newActionOrder[] = $actionName;
            }
        }

        $this->dto->reorderActions($pageName, $newActionOrder);

        return $this;
    }

    public function setPermission(string $actionName, string $permission): self
    {
        $this->dto->setActionPermission($actionName, $permission);

        return $this;
    }

    /**
     * @param string[] $permissions Syntax: ['actionName' => 'actionPermission', ...]
     */
    public function setPermissions(array $permissions): self
    {
        $this->dto->setActionPermissions($permissions);

        return $this;
    }

    public function disable(string ...$disabledActionNames): self
    {
        // if 'delete' is disabled, 'batch delete' is disabled automatically (but the
        // opposite doesn't happen). This is the most common case, but user can re-enable
        // the 'batch delete' action if needed manually
        if (\in_array(Action::DELETE, $disabledActionNames, true)) {
            $disabledActionNames[] = Action::BATCH_DELETE;
        }

        $this->dto->disableActions($disabledActionNames);

        return $this;
    }

    public function getAsDto(string $pageName): ActionConfigDto
    {
        $this->dto->setPageName($pageName);

        return $this->dto;
    }

    /**
     * The $pageName is needed because sometimes the same action has different config
     * depending on where it's displayed (to display an icon in 'detail' but not in 'index', etc.).
     */
    private function createBuiltInAction(string $pageName, string $actionName): Action
    {
        assert(null !== $this->metadata, 'Metadata must be set to create built-in actions.');

        $applicationName = $this->metadata ? $this->metadata->getApplicationName() : 'app';
        $name = $this->metadata ? $this->metadata->getName() : 'resource';

        if (Action::BATCH_DELETE === $actionName) {
            return Action::new(Action::BATCH_DELETE, '', null)
                ->createAsBatchAction()
                ->setSyliusAction(DeleteAction::create([]));
        }

        if (Action::NEW === $actionName) {
            return Action::new(Action::NEW, '', null)
                ->createAsGlobalAction()
                ->setSyliusAction(CreateAction::create([]));
        }

        if (Action::EDIT === $actionName) {
            $action = UpdateAction::create([]);

            return Action::new(
                Action::EDIT,
                \sprintf('%s.%s.admin.action.edit', $applicationName, $name),
                null,
            )->setSyliusAction($action);
        }

        if (Action::DETAIL === $actionName) {
            return Action::new(
                Action::DETAIL,
                \sprintf('%s.%s.admin.action.show', $applicationName, $name),
                'tabler:eye',
            )->setSyliusAction(ShowAction::create([]));
        }

        if (Action::INDEX === $actionName && $this->requestConfiguration) {
            $action = SyliusAction::create(Action::INDEX, 'easy_crud_main_action')
                ->setLabel(\sprintf('%s.%s.admin.action.index', $applicationName, $name))
                ->setOptions([
                    'link' => [
                        'route' => $this->requestConfiguration->getRouteName('index'),
                    ],
                ])
                ->setIcon('tabler:list');

            return Action::new(
                Action::INDEX,
                \sprintf('%s.%s.admin.action.index', $applicationName, $name),
                'null',
            )->setSyliusAction($action);
        }

        if (Action::DELETE === $actionName) {
            return Action::new(Action::DELETE, '', null)
                ->setSyliusAction(DeleteAction::create([]));
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }

    private function doAddAction(
        string $pageName,
        Action|string $actionNameOrObject,
        bool $isBatchAction = false,
        bool $isGlobalAction = false,
    ): self {
        $actionName = \is_string($actionNameOrObject) ? $actionNameOrObject : (string) $actionNameOrObject;
        $action = \is_string($actionNameOrObject) ? $this->createBuiltInAction($pageName, $actionNameOrObject) : $actionNameOrObject;

        if (null !== $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists in the "%s" page, so you can\'t add it again. Instead, you can use the "updateAction()" method to update any options of an existing action.', $actionName, $pageName));
        }

        $actionDto = $action->getAsDto();
        if ($isBatchAction) {
            $actionDto->setType(Action::TYPE_BATCH);
        }
        if ($isGlobalAction) {
            $actionDto->setType(Action::TYPE_GLOBAL);
        }

        if (Crud::PAGE_INDEX === $pageName && Action::DELETE === $actionName) {
            $this->dto->prependAction($pageName, $actionDto);
        } else {
            $this->dto->appendAction($pageName, $actionDto);
        }

        return $this;
    }

    public function setMetadata(?Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function setRequestConfiguration(?RequestConfiguration $requestConfiguration): void
    {
        $this->requestConfiguration = $requestConfiguration;
    }
}
