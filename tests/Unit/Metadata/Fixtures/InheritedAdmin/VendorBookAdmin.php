<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedAdmin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Magazine;

/**
 * A concrete Admin shipped by a vendor (e.g. a plugin), declaring its model with the method.
 */
class VendorBookAdmin extends AbstractAdmin
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
