<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusEasyCrudExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('sylius_easy_crud.attributes.enabled', $config['attributes']['enabled']);
        $container->setParameter('sylius_easy_crud.attributes.paths', $config['attributes']['paths']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services_maker.yaml');
        $loader->load('services.yaml');

        /**
         * @var array<string, mixed> $actionTemplates
         */
        $actionTemplates = $container->getParameter('sylius.grid.templates.action');
        $actionTemplates['easy_crud_main_action'] = '@SyliusEasyCrudPlugin/crud/action/action.html.twig';
        $actionTemplates['easy_crud_main_sub_action'] = '@SyliusEasyCrudPlugin/crud/action/links.html.twig';
        $actionTemplates['easy_crud_item_action'] = '@SyliusEasyCrudPlugin/crud/action/action.html.twig';
        $actionTemplates['easy_crud_item_sub_action'] = '@SyliusEasyCrudPlugin/crud/action/list.html.twig';
        $container->setParameter('sylius.grid.templates.action', $actionTemplates);

        /**
         * @var array<string, mixed> $bulkActionTemplates
         */
        $bulkActionTemplates = $container->getParameter('sylius.grid.templates.bulk_action');
        $bulkActionTemplates['easy_crud_batch_sub_action'] = '@SyliusEasyCrudPlugin/crud/action/links.html.twig';
        $container->setParameter('sylius.grid.templates.bulk_action', $bulkActionTemplates);

        $container->registerForAutoconfiguration(FieldConfiguratorInterface::class)
            ->addTag('easy.fields.configurator')
        ;

        $container->registerForAutoconfiguration(AdminInterface::class)
            ->addTag('sylius_easy_crud_admin');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
        $this->prependAttributeResources($container);
    }

    /**
     * Scans #[AsEasyCrudAdmin] entities and translates them into legacy Sylius
     * resources (registered through sylius_resource) plus routing configs replayed
     * by EasyCrudAttributesRoutesLoader. Done in prepend() so the resources are
     * processed by SyliusResourceExtension::load() like any YAML-declared resource.
     */
    private function prependAttributeResources(ContainerBuilder $container): void
    {
        $attributes = $this->resolveAttributesConfig($container);

        // These parameters are always consumed downstream (the route loader argument
        // and the legacy-deprecation compiler pass), so they must exist even when the
        // attribute discovery is disabled.
        if (!$attributes['enabled'] || [] === $attributes['paths']) {
            $container->setParameter('sylius_easy_crud.attributes.routing', []);
            $container->setParameter('sylius_easy_crud.attributes.declared_aliases', []);

            return;
        }

        $descriptors = EasyCrudResourceFactory::build($attributes['paths']);

        $resources = [];
        $routing = [];
        foreach ($descriptors as $descriptor) {
            $resources[$descriptor['alias']] = $descriptor['registry'];
            $routing[] = $descriptor['routing'];
        }

        if ([] !== $resources) {
            $container->prependExtensionConfig('sylius_resource', ['resources' => $resources]);
        }

        $container->setParameter('sylius_easy_crud.attributes.routing', $routing);
        $container->setParameter('sylius_easy_crud.attributes.declared_aliases', array_keys($resources));
    }

    /**
     * Reads the raw (not-yet-processed) bundle config in prepend() and resolves
     * %parameter% placeholders / non-existent directories in the paths.
     *
     * @return array{enabled: bool, paths: list<string>}
     */
    private function resolveAttributesConfig(ContainerBuilder $container): array
    {
        $enabled = false;
        $paths = [];

        foreach ($container->getExtensionConfig('sylius_easy_crud') as $config) {
            $attributes = $config['attributes'] ?? null;
            if (!is_array($attributes)) {
                continue;
            }
            if (array_key_exists('enabled', $attributes)) {
                // last block wins, like Symfony's scalar config merge
                $enabled = (bool) $attributes['enabled'];
            }
            if (array_key_exists('paths', $attributes) && is_array($attributes['paths'])) {
                // merge across all config blocks (packages/, env, plugin overrides) instead of
                // letting the last one overwrite the others, mirroring Symfony's list merge
                $paths = array_merge($paths, array_values($attributes['paths']));
            }
        }

        return [
            'enabled' => $enabled,
            'paths' => $this->resolveExistingDirectories($container, array_values(array_unique($paths))),
        ];
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function resolveExistingDirectories(ContainerBuilder $container, array $paths): array
    {
        $resolved = [];
        foreach ($paths as $path) {
            /** @var string $real */
            $real = $container->getParameterBag()->resolveValue($path);
            if (is_dir($real)) {
                $resolved[] = $real;
            }
        }

        return $resolved;
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SyliusEasyCrudPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }

    public function getAlias(): string
    {
        return 'sylius_easy_crud';
    }
}
