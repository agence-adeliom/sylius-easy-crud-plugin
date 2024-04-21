<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;

interface AdminInterface
{
    public function configureFields(string $pageName, ?string $context = null): iterable;

    public function configureActions(string $pageName): Actions;

    public function configureFilters(): iterable;

    public static function getEntityFqcn(): string;

    public static function getName(): string;
}
