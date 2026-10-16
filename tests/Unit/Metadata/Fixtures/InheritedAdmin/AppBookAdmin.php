<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedAdmin;

use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * The application extends the vendor Admin and points it to its own model with the attribute.
 */
#[AsAdmin(resourceClass: Book::class, alias: 'app.inherited_book')]
final class AppBookAdmin extends VendorBookAdmin
{
}
