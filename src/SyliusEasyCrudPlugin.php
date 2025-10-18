<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusEasyCrudPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAdminPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
