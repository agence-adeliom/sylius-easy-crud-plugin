<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

trait EntityPublishableTrait
{
    #[Groups('main')]
    #[Assert\NotBlank]
    #[ORM\Column(length: 100)]
    private string $publishState;

    #[Groups('main')]
    #[ORM\Column(name: 'publish_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $publishDate;

    #[Groups('main')]
    #[Assert\Expression(
        expression: 'this.getUnpublishDate() == null or this.getUnpublishDate() > this.getPublishDate()',
        message: 'The unpublish date must be greater than the publish date',
    )]
    #[ORM\Column(name: 'unpublish_date', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $unpublishDate = null;

    /**
     * PublishableTrait constructor.
     */
    public function __construct()
    {
        $this->publishDate = null;
        $this->unpublishDate = null;
        $this->publishedState = ThreeStateStatusEnum::UNPUBLISHED();
    }

    public function getPublishState(): ?string
    {
        return $this->publishState;
    }

    public function setPublishState(?string $state): void
    {
        if ($state) {
            ThreeStateStatusEnum::assertValidValue($state);
        }

        $this->publishState = $state;
    }

    public function getPublishDate(): ?\DateTimeInterface
    {
        return $this->publishDate;
    }

    public function setPublishDate(?\DateTimeInterface $publishDate): self
    {
        $this->publishDate = $publishDate;

        return $this;
    }

    public function getUnpublishDate(): ?\DateTimeInterface
    {
        return $this->unpublishDate;
    }

    public function setUnpublishDate(?\DateTimeInterface $unpublishDate): self
    {
        $this->unpublishDate = $unpublishDate;

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->hasState(ThreeStateStatusEnum::PUBLISHED()->getValue()) && $this->isDatePublished();
    }

    public function previewIsAvailable(): bool
    {
        return !$this->hasState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
    }

    public function isStatePublished(): bool
    {
        return $this->hasState(ThreeStateStatusEnum::PUBLISHED()->getValue());
    }

    public function isStateUnpublished(): bool
    {
        return $this->hasState(ThreeStateStatusEnum::UNPUBLISHED()->getValue());
    }

    public function isStatePending(): bool
    {
        return $this->hasState(ThreeStateStatusEnum::PENDING()->getValue());
    }

    public function hasState(?string $state): bool
    {
        return strtolower($this->publishState) === strtolower($state);
    }

    public function isDatePublished(): bool
    {
        $now = new \DateTime();

        if (null === $this->getPublishDate() && null === $this->getUnpublishDate()) {
            return true;
        }

        return
            (null === $this->getUnpublishDate() && $this->getPublishDate() <= $now) ||
            ($now <= $this->getUnpublishDate() && $this->getPublishDate() <= $now);
    }
}
