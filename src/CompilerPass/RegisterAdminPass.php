<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterAdminPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Register all services tagged with 'sylius_easy_crud_admin'
        // As a Sylius Grid admin with autowiring and entity FQCN argument
        foreach ($container->findTaggedServiceIds('sylius_easy_crud_admin') as $id => $tags) {
            $definition = $container->getDefinition((string) $id);
            $definition->addArgument($definition->getClass()::getEntityFqcn());
            $definition->addArgument([]);
            $definition->setAutowired(true);
        }
    }
}
