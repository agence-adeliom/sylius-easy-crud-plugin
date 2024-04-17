<?php

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterAdminPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('sylius_easy_crud') as $id => $tags) {
            $definition = $container->getDefinition((string) $id);
            $definition->addArgument($definition->getClass()::getEntityFqcn());
            $definition->addArgument([]);
            $definition->setAutowired(true);
        }
    }
}
