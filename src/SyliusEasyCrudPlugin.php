<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SyliusEasyCrudPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAdminPass());
    }
}
