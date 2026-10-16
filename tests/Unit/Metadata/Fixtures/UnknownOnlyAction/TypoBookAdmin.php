<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\UnknownOnlyAction;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

#[AsAdmin(resourceClass: Book::class, alias: 'app.typo_only_book', only: ['index', 'indx'])]
final class TypoBookAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
