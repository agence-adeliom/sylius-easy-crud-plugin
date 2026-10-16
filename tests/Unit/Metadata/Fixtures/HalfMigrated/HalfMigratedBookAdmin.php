<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\HalfMigrated;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * Migrated to #[AsAdmin] (getName() and getEntityFqcn() removed) while its model has no #[AsResource] and the
 * attribute discovery is still disabled: its grid name cannot be derived.
 */
#[AsAdmin(resourceClass: Book::class)]
final class HalfMigratedBookAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
