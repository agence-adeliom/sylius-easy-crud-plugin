<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Entity\PositionSort;

use Adeliom\SyliusEasyCrudPlugin\Traits\PositionSortableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A Gedmo nested tree, for the tests of the PositionSortableField (tests/Integration/PositionSort).
 */
// Gedmo's NestedTreeRepository::persistAsNextSiblingOf() & co. write `$node->sibling` when the node has no setSibling().
#[\AllowDynamicProperties]
#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: NestedTreeRepository::class)]
#[ORM\Table(name: 'test_position_sort_tree_item')]
class TreeItem implements ResourceInterface
{
    use PositionSortableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $children;

    public function __construct(string $name, ?self $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
