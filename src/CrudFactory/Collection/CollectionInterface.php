<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 *
 * @extends ArrayAccess<int, mixed>
 * @extends IteratorAggregate<int, mixed>
 */
interface CollectionInterface extends ArrayAccess, Countable, IteratorAggregate
{
}
