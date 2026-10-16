<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\DivergentResourceClass;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Magazine;

#[AsAdmin(resourceClass: Book::class, alias: 'app.book')]
final class DivergentResourceClassAdmin extends AbstractAdmin
{
    public static function getEntityFqcn(): string
    {
        return Magazine::class;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
