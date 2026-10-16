<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * A resource migrated from a legacy YAML block that must stay byte-identical:
 * generic repository, vars written in full, permission checks on.
 */
#[AsAdmin(
    resourceClass: Book::class,
    alias: 'app.legacy_book',
    repository: EntityRepository::class,
    grid: 'app_admin_legacy_book',
    permission: true,
    except: ['show'],
    vars: ['all' => ['icon' => 'tabler:book', 'subheader' => false]],
    defaultVars: false,
    resource: ['state_machine_component' => 'symfony'],
)]
final class LegacyBookAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        return [];
    }
}
