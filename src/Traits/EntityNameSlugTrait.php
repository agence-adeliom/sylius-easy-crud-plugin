<?php

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Doctrine\ORM\Mapping\Column;
use Gedmo\Mapping\Annotation\Slug;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

trait EntityNameSlugTrait
{
    #[Groups('main')]
    #[NotBlank]
    #[Length(max: 255)]
    #[Column(name: "name", length: 255, nullable: true)]
    private ?string $name = null;

    #[Groups('main')]
    #[Slug(fields: ['name'], updatable: false, unique: false)]
    #[Column(name: "slug", length: 255, nullable: true)]
    private ?string $slug = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
