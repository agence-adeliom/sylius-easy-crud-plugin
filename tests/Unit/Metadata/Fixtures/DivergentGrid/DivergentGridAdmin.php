<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\DivergentGrid;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

#[AsAdmin(resourceClass: Book::class, alias: 'app.book', grid: 'app_admin_book')]
final class DivergentGridAdmin extends AbstractAdmin
{
    public static function getName(): string
    {
        return 'custom_book';
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
