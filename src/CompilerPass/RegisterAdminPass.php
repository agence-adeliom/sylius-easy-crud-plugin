<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
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
            /** @var class-string|null $class */
            $class = $definition->getClass();
            if ($class) {
                $definition->addArgument($this->resolveResourceClass($class));
                $definition->addArgument([]);
                $definition->setAutowired(true);
            }
        }
    }

    /**
     * @param class-string $class
     */
    private function resolveResourceClass(string $class): string
    {
        $reflectionClass = new \ReflectionClass($class);
        if (
            !$this->declaresEntityFqcn($reflectionClass) &&
            [] !== $attributes = $reflectionClass->getAttributes(AsAdmin::class)
        ) {
            $attribute = $attributes[0]->newInstance();
            if ($attribute instanceof AsAdmin && null !== $attribute->resourceClass) {
                return $attribute->resourceClass;
            }
        }

        if (!method_exists($class, 'getEntityFqcn')) {
            throw new \LogicException(sprintf('The easy-crud admin "%s" must define getEntityFqcn() or #[AsAdmin(resourceClass: ...)].', $class));
        }

        /** @var string $resourceClass */
        $resourceClass = $class::getEntityFqcn();

        return $resourceClass;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private function declaresEntityFqcn(\ReflectionClass $reflectionClass): bool
    {
        return $reflectionClass->hasMethod('getEntityFqcn') &&
            $reflectionClass->getMethod('getEntityFqcn')->getDeclaringClass()->getName() === $reflectionClass->getName();
    }
}
