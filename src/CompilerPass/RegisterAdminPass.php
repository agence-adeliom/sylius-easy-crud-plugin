<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\Metadata\AdminDeclarationResolver;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RegisterAdminPass implements CompilerPassInterface
{
    /**
     * Public locator of the Admins indexed by class, for the tools inspecting the application from outside (the Mate
     * tools): the Admins are private form types, reachable otherwise only by building their form.
     */
    public const ADMIN_LOCATOR = 'sylius_easy_crud.admin_locator';

    public function process(ContainerBuilder $container): void
    {
        // Absent when the pass runs without the extension: the attribute discovery is then considered enabled.
        $attributesEnabled = !$container->hasParameter('sylius_easy_crud.attributes.enabled') ||
            true === $container->getParameter('sylius_easy_crud.attributes.enabled');

        /** @var array<string, array<class-string, true>> $adminsByGridName */
        $adminsByGridName = [];
        /** @var array<class-string, string> $ignoredAttributes grid name by Admin class */
        $ignoredAttributes = [];
        /** @var array<class-string, ServiceClosureArgument> $admins */
        $admins = [];

        // Register all services tagged with 'sylius_easy_crud_admin'
        // As a Sylius Grid admin with autowiring and entity FQCN argument
        foreach ($container->findTaggedServiceIds('sylius_easy_crud_admin') as $id => $tags) {
            $definition = $container->getDefinition((string) $id);
            /** @var class-string|null $class */
            $class = $definition->getClass();
            if ($class && !$definition->isAbstract()) {
                AdminDeclarationResolver::assertConsistent($class);
                if (method_exists($class, 'getName')) {
                    $gridName = self::gridName($class, $attributesEnabled);
                    $adminsByGridName[$gridName][$class] = true;
                    if (!$attributesEnabled && null !== AdminDeclarationResolver::attribute($class)) {
                        $ignoredAttributes[$class] = $gridName;
                    }
                }
                $definition->addArgument(AdminDeclarationResolver::resolveResourceClass($class));
                $definition->addArgument([]);
                $definition->setAutowired(true);
                $admins[$class] = new ServiceClosureArgument(new Reference((string) $id));
                // The Doctrine type guesser has no autowiring alias: bind it to the Admins only, instead of
                // registering a global alias in the host application.
                $definition->setBindings($definition->getBindings() + [
                    DoctrineOrmTypeGuesser::class => new BoundArgument(new Reference('form.type_guesser.doctrine'), false),
                ]);
            }
        }

        $container->register(self::ADMIN_LOCATOR, ServiceLocator::class)
            ->setPublic(true)
            ->setArguments([$admins])
            ->addTag('container.service_locator');

        self::assertUniqueGridNames($adminsByGridName);
        self::reportIgnoredAttributes($ignoredAttributes);
    }

    /**
     * @param class-string $class
     */
    private static function gridName(string $class, bool $attributesEnabled): string
    {
        try {
            /** @var string $gridName */
            $gridName = $class::getName();

            return $gridName;
        } catch (\LogicException $exception) {
            if ($attributesEnabled || null === AdminDeclarationResolver::attribute($class)) {
                throw $exception;
            }

            throw new \LogicException(sprintf(
                'The easy-crud admin "%s" carries #[AsAdmin] but "sylius_easy_crud.attributes.enabled" is false: the ' .
                'attribute does not declare its resource nor its routes, which still come from the YAML blocks, and its ' .
                'grid name, derived from the resource alias, cannot be resolved (%s). Either set ' .
                '"sylius_easy_crud.attributes.enabled: true" (with "paths" covering the Admin) and remove the YAML blocks ' .
                'of the resource, or keep the resource in YAML and set #[AsAdmin(grid:)] (or getName()) to the "grid" of ' .
                'its "type: sylius.resource" route.',
                $class,
                $exception->getMessage(),
            ), 0, $exception);
        }
    }

    /**
     * An Admin migrated to #[AsAdmin] while the attribute discovery is disabled is "half migrated": its resource and its
     * routes still come from the YAML blocks, and Sylius only finds its grid when the "grid" of its YAML route equals
     * its getName(), which the attribute may now derive. Reported as a deprecation, like the YAML declaration itself,
     * so that it shows without failing the build.
     *
     * @param array<class-string, string> $ignoredAttributes grid name by Admin class
     */
    private static function reportIgnoredAttributes(array $ignoredAttributes): void
    {
        if ([] === $ignoredAttributes) {
            return;
        }

        $admins = [];
        foreach ($ignoredAttributes as $class => $gridName) {
            $admins[] = sprintf('"%s" (grid "%s")', $class, $gridName);
        }

        trigger_deprecation(
            'agence-adeliom/sylius-easy-crud-plugin',
            '2.4',
            'The easy-crud admins %s carry #[AsAdmin] while "sylius_easy_crud.attributes.enabled" is false: the attribute ' .
            'only provides the defaults of these Admins (resource class, grid name, sort, limits…), their resources and ' .
            'routes are still read from the YAML blocks, whose route "grid" must equal the grid name above. Set ' .
            '"sylius_easy_crud.attributes.enabled: true" (with "paths" covering them) and remove their YAML blocks to ' .
            'declare them with the attribute.',
            implode(', ', $admins),
        );
    }

    /**
     * Sylius registers one grid per getName() (a service locator indexed by name): two Admins returning the same name
     * would share one grid, the other one being silently dropped. It happens when an Admin extends another concrete
     * Admin without overriding getName().
     *
     * @param array<string, array<class-string, true>> $adminsByGridName
     */
    private static function assertUniqueGridNames(array $adminsByGridName): void
    {
        foreach ($adminsByGridName as $gridName => $classes) {
            if (\count($classes) < 2) {
                continue;
            }

            throw new \LogicException(sprintf(
                'Several easy-crud admins use the grid name "%s" (returned by getName()): "%s". Sylius registers one grid ' .
                'per name, so only one of them would be used. Give each Admin its own name: override getName() (an Admin ' .
                'extending another concrete Admin inherits its name), or set #[AsAdmin(grid:)].',
                $gridName,
                implode('", "', array_keys($classes)),
            ));
        }
    }
}
