<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book implements ResourceInterface
{
    public function getId(): ?int
    {
        return null;
    }
}
