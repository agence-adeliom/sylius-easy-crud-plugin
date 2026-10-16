<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\NoAttribute;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

final class FAQAdmin extends AbstractAdmin
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }
}
