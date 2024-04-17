<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Repository;

use Doctrine\ORM\QueryBuilder;

interface TranslationRepositoryInterface
{
    public function createListQueryBuilder(string $locale): QueryBuilder;
}
