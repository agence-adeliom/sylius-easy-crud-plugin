<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\CollectionInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\ActionDto;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
final class ActionCollection implements CollectionInterface
{
    /**
     * @param ActionDto[] $actions
     */
    private function __construct(private array $actions)
    {
    }

    public function __clone()
    {
        foreach ($this->actions as $actionName => $actionDto) {
            $this->actions[$actionName] = clone $actionDto;
        }
    }

    /**
     * @param ActionDto[] $actions
     */
    public static function new(array $actions): self
    {
        return new self($actions);
    }

    /**
     * @return ActionDto[]
     */
    public function all(): array
    {
        return $this->actions;
    }

    public function get(string $actionName): ?ActionDto
    {
        return $this->actions[$actionName] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->actions);
    }

    public function offsetGet(mixed $offset): ActionDto
    {
        return $this->actions[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->actions[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->actions[$offset]);
    }

    public function count(): int
    {
        return \count($this->actions);
    }

    /**
     * @return \ArrayIterator<ActionDto>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->actions);
    }

    public function getItemActions(): self
    {
        return self::new(array_filter(
            $this->actions,
            static fn (ActionDto $action): bool => $action->isItemAction(),
        ));
    }

    public function getGlobalActions(): self
    {
        return self::new(array_filter(
            $this->actions,
            static fn (ActionDto $action): bool => $action->isGlobalAction(),
        ));
    }

    public function getBatchActions(): self
    {
        return self::new(array_filter(
            $this->actions,
            static fn (ActionDto $action): bool => $action->isBatchAction(),
        ));
    }
}
