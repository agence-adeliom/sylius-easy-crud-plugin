<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\ConsistentAdmin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * The attribute and the overridden methods declare the same values.
 */
#[AsAdmin(resourceClass: Book::class, alias: 'app.book', grid: 'custom_book')]
final class ConsistentAdmin extends AbstractAdmin
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public static function getName(): string
    {
        return 'custom_book';
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
