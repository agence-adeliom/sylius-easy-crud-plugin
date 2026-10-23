<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Model;

/**
 * A resource ordered by an integer `position` property, sortable by drag and drop in its admin list
 * with {@see \Adeliom\SyliusEasyCrudPlugin\Admin\Field\PositionSortableField}.
 *
 * The Doctrine field must be named `position`: the other rows are shifted with a DQL query on it.
 */
interface PositionSortableInterface
{
    public function getId(): mixed;

    public function getPosition(): ?int;

    // null: no position, the optional field of the forms left empty
    public function setPosition(?int $position): void;
}
