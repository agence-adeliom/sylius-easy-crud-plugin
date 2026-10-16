<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\SharedGridName;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * A concrete Admin, itself registered, declaring its grid name with the method.
 */
class ParentBookAdmin extends AbstractAdmin
{
    public static function getName(): string
    {
        return 'shared_book';
    }

    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
