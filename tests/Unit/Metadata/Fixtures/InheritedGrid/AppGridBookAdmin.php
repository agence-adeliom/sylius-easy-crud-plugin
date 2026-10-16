<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedGrid;

use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * Sets a grid name with the attribute, but inherits getName() from the vendor Admin.
 */
#[AsAdmin(resourceClass: Book::class, alias: 'app.book', grid: 'app_admin_book')]
final class AppGridBookAdmin extends VendorGridBookAdmin
{
}
