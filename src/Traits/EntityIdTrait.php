<?php

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation as Serializer;

trait EntityIdTrait
{
    /**
     * The unique auto incremented primary key.
     */
    #[Groups('Default')]
    #[ORM\Id]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    #[Serializer\Expose]
    #[Serializer\Type('integer')]
    #[Serializer\Groups(['Detailed', 'Default', 'Autocomplete'])]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
