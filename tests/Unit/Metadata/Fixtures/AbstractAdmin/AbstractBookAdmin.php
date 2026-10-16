<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\AbstractAdmin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * A base Admin meant to be extended: never registered as a resource, even with #[AsAdmin].
 */
#[AsAdmin(resourceClass: Book::class, alias: 'app.abstract_book')]
abstract class AbstractBookAdmin extends AbstractAdmin
{
}
