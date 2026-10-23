<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Positions of a flat list sorted by drag and drop ({@see \Adeliom\SyliusEasyCrudPlugin\Admin\Field\PositionSortableField}).
 */
interface PositionShifterInterface
{
    /**
     * Numbers the rows 0 to n-1 in the order of the list, so that every row has a distinct position.
     *
     * @param class-string<PositionSortableInterface> $class
     */
    public function renumber(EntityManagerInterface $entityManager, string $class): void;

    /**
     * Moves the resource to the position and shifts the rows between its old and its new position.
     */
    public function move(EntityManagerInterface $entityManager, PositionSortableInterface $resource, int $position): void;
}
