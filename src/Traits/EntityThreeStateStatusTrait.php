<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait EntityThreeStateStatusTrait
{
    #[Groups('main')]
    #[Assert\NotBlank]
    #[ORM\Column(length: 100)]
    private string $state;

    /**
     * EntityThreeStateStatusTrait constructor.
     */
    public function __construct()
    {
        $this->state = ThreeStateStatusEnum::UNPUBLISHED();
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        if ($state) {
            ThreeStateStatusEnum::assertValidValue($state);
        }

        $this->state = $state;
    }

    public function isState(?string $state): bool
    {
        return $this->state == ThreeStateStatusEnum::search($state);
    }
}
