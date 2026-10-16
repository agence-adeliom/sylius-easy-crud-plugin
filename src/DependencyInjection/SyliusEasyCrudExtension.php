<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusEasyCrudExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    /** @psalm-suppress UnusedVariable */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{attributes: array{enabled: bool, paths: list<string>}} $config */
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
     * Scans #[AsAdmin] admins and contributes, for each, the full legacy Sylius resource
     * (registered through sylius_resource) plus the routing config replayed by
     * EasyCrudAttributesRoutesLoader. Done in prepend() — before SyliusResourceExtension::load()
     * materializes per-resource services — so the easy-crud controller/form/translation are
     * applied. The resource alias comes from the native #[AsResource] on the model;
     * autoRegisterResources skips any alias we declare here, so easy-crud owns the entry.
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
        $yamlAliases = $this->collectConfiguredResourceAliases($container);

        $resources = [];
        $routing = [];
        foreach ($descriptors as $descriptor) {
            if (isset($yamlAliases[$descriptor['alias']])) {
                throw new \LogicException(sprintf(
                    'The easy-crud resource "%1$s" is declared twice: with #[AsAdmin] on "%2$s" and with a ' .
                    '"sylius_resource.resources.%1$s" configuration block (legacy YAML). Remove the YAML block (and its ' .
                    '"type: sylius.resource" route), or the attribute.',
                    $descriptor['alias'],
                    $descriptor['admin'],
                ));
            }

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
     * The aliases already declared under `sylius_resource.resources` by the application or another bundle.
     *
     * @return array<string, true>
     */
    private function collectConfiguredResourceAliases(ContainerBuilder $container): array
    {
        $aliases = [];
        foreach ($container->getExtensionConfig('sylius_resource') as $config) {
            $resources = self::configValue($config, 'resources');
            if (!is_array($resources)) {
                continue;
            }
            foreach (array_keys($resources) as $alias) {
                $aliases[(string) $alias] = true;
            }
        }

        return $aliases;
    }

    /**
     * A key of one raw extension config block. Symfony 8.2 types these blocks `mixed` (a config tree may accept a
     * non-array root), earlier versions `array<string, mixed>`.
     */
    private static function configValue(mixed $config, string $key): mixed
    {
        return is_array($config) ? ($config[$key] ?? null) : null;
    }

    /**
     * Reads the raw (not-yet-processed) bundle config in prepend(): %parameter% placeholders are resolved, environment
     * variables are refused (their value is unknown while the container is built) and, when the discovery is enabled,
     * every path must be an existing directory.
     *
     * @return array{enabled: bool, paths: list<string>}
     */
    private function resolveAttributesConfig(ContainerBuilder $container): array
    {
        $enabled = false;
        $paths = [];

        foreach ($container->getExtensionConfig('sylius_easy_crud') as $config) {
            $attributes = self::configValue($config, 'attributes');
            if (!is_array($attributes)) {
                continue;
            }
            if (array_key_exists('enabled', $attributes)) {
                // last block wins, like Symfony's scalar config merge
                $enabled = $this->resolveEnabled($container, $attributes['enabled']);
            }
            if (array_key_exists('paths', $attributes) && is_array($attributes['paths'])) {
                // merge across all config blocks (packages/, env, plugin overrides) instead of
                // letting the last one overwrite the others, mirroring Symfony's list merge
                /** @var list<string> $configuredPaths */
                $configuredPaths = array_values($attributes['paths']);
                $paths = array_merge($paths, $configuredPaths);
            }
        }

        return [
            'enabled' => $enabled,
            'paths' => $enabled ? $this->resolveDirectories($container, array_values(array_unique($paths))) : [],
        ];
    }

    private function resolveEnabled(ContainerBuilder $container, mixed $value): bool
    {
        if (is_string($value) && str_contains($value, '%env(')) {
            throw new InvalidConfigurationException(sprintf(
                'The "sylius_easy_crud.attributes.enabled" option cannot be set with an environment variable ("%s"): ' .
                'the #[AsAdmin] resources are discovered when the container is built, before environment variables ' .
                'are available. Set a boolean, or a parameter holding a boolean.',
                $value,
            ));
        }

        $resolved = $container->getParameterBag()->resolveValue($value);
        if (null === $resolved) {
            return false;
        }

        if (!is_bool($resolved)) {
            throw new InvalidConfigurationException(sprintf(
                'The "sylius_easy_crud.attributes.enabled" option must be a boolean known when the container is built, ' .
                '%s given (%s). Environment variables are not supported: the #[AsAdmin] resources are discovered when ' .
                'the container is built.',
                get_debug_type($resolved),
                is_scalar($resolved) ? sprintf('"%s"', (string) $resolved) : 'from ' . (is_string($value) ? $value : get_debug_type($value)),
            ));
        }

        return $resolved;
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function resolveDirectories(ContainerBuilder $container, array $paths): array
    {
        $resolved = [];
        foreach ($paths as $path) {
            /** @var string $real */
            $real = $container->getParameterBag()->resolveValue($path);
            if (!is_dir($real)) {
                throw new InvalidConfigurationException(sprintf(
                    'The directory "%s" listed in "sylius_easy_crud.attributes.paths" (as "%s") does not exist: ' .
                    'fix or remove it, the #[AsAdmin] classes are discovered in these directories.',
                    $real,
                    $path,
                ));
            }
            // the #[AsAdmin] classes are read when the container is built: rebuild it when one is added or changed,
            // also when the directory is not covered by the service resources of the application
            $container->addResource(new DirectoryResource($real, '/\.php$/'));
            $resolved[] = $real;
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
