<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Command;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:easy-crud:create-crud')]
final class StubCreateEasyCrud extends StubCommand
{
}
