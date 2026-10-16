<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model;

use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Model\ResourceInterface;

/**
 * A resource without explicit alias: Sylius derives "app.novel" (default application, short name without "Resource").
 */
#[AsResource]
class NovelResource implements ResourceInterface
{
    public function getId(): ?int
    {
        return null;
    }
}
