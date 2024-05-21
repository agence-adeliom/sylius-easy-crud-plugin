<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SyliusEasyCrudPlugin extends AbstractBundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return __DIR__;
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAdminPass());
    }
}
