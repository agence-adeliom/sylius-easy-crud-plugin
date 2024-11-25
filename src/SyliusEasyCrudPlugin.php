<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Adeliom\SyliusEasyCrudPlugin\DependencyInjection\SyliusEasyCrudExtension;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SyliusEasyCrudPlugin extends AbstractBundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterAdminPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SyliusEasyCrudExtension();
    }
}
