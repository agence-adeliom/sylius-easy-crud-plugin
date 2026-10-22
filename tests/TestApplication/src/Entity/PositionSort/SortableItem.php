<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Entity\PositionSort;

use Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A flat list sorted by position, for the tests of the PositionSortableField (tests/Integration/PositionSort).
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_position_sort_sortable_item')]
class SortableItem implements ResourceInterface, PositionSortableInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
