<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedOverriddenAdmin;

use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedAdmin\VendorBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * The application extends the vendor Admin, points it to its own model and overrides getEntityFqcn() accordingly.
 */
#[AsAdmin(resourceClass: Book::class, alias: 'app.inherited_book')]
final class OverridingBookAdmin extends VendorBookAdmin
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }
}
