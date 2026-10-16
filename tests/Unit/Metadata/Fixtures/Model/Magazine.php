<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A model whose Doctrine repository is a plain ServiceEntityRepository, unusable as a Sylius resource repository.
 */
#[AsResource(alias: 'library.magazine')]
#[ORM\Entity(repositoryClass: MagazineRepository::class)]
class Magazine implements ResourceInterface
{
    public function getId(): ?int
    {
        return null;
    }
}
