<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Sylius\Resource\Factory\Factory;
use Sylius\Resource\Factory\TranslatableFactory;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TranslatableFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('sylius.resource_factory') as $id => $tags) {
            $definition = $container->getDefinition($id);

            if (Factory::class !== $definition->getClass()) {
                continue;
            }
            dump($definition);

            $arguments = $definition->getArguments();
            if (empty($arguments) || !is_string($arguments[0])) {
                continue;
            }

            /** @var class-string $modelClass */
            $modelClass = $arguments[0];

            if (!is_a($modelClass, TranslatableInterface::class, true)) {
                continue;
            }

            $innerFactory = new Definition(Factory::class, [$modelClass]);

            $definition->setClass(TranslatableFactory::class);
            $definition->setArguments([
                $innerFactory,
                new Reference('sylius.translation_locale_provider'),
            ]);
        }
    }
}
