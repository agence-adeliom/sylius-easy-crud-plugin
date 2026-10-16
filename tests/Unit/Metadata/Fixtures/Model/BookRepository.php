<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Book>
 */
final class BookRepository extends EntityRepository
{
}
