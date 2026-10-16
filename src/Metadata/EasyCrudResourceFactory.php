<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Doctrine\ORM\Mapping\Entity;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\TranslatableFactory;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Reflection\ClassReflection;
use Sylius\Resource\Reflection\ReflectionClassRecursiveIterator;
use function Symfony\Component\String\u;

/**
 * Scans the configured paths for Admin classes carrying #[AsAdmin] and translates
 * each one into the legacy Sylius resource configuration + routing configuration
 * that the config/routes.yaml + sylius_resource.yaml blocks used to provide.
 *
 * The resource identity (alias) is owned by the native #[\Sylius\Resource\Metadata\AsResource]
 * declared on the model ($resourceClass): we read it back by reflection. The full
 * resource entry is then contributed in the bundle's prepend() via
 * prependExtensionConfig('sylius_resource', …). Sylius' own #[AsResource] auto-registration
 * (autoRegisterResources) skips any alias already declared, so easy-crud owns the entry
 * (controller => SyliusCrudResourceController, form => the Admin, repository, translation).
 *
 * This is required because the Sylius resource driver materializes the per-resource
 * services (controller, repository, translation sub-resource) during load(), i.e. before
 * any compiler pass — so the controller class / translation sub-resource cannot be patched
 * after the fact and must be present in the resource config from prepend().
 *
 * Pure helper (no DI): it runs at container build time from the bundle extension's prepend().
 *
 * @phpstan-type ResourceDescriptor array{
 *     alias: string,
 *     admin: class-string<AdminInterface>,
 *     registry: array<string, mixed>,
 *     routing: array{prefix: string, config: array<string, mixed>}
 * }
 */
final class EasyCrudResourceFactory
{
    public const DEFAULT_APPLICATION_NAME = 'app';

    public const DEFAULT_CONTROLLER = SyliusCrudResourceController::class;

    public const DEFAULT_TRANSLATION_PREFIX = 'sylius_easy_crud_plugin';

    /** The actions the `sylius.resource` route loader generates, that `only` / `except` may list. */
    public const ACTIONS = ['show', 'index', 'create', 'update', 'delete', 'bulkDelete'];

    /**
     * @param list<string> $paths Directories holding the easy-crud Admin classes.
     *
     * @return list<ResourceDescriptor>
     *
     * @throws \LogicException when two Admins declare the same resource alias
     */
    public static function build(array $paths): array
    {
        if ([] === $paths) {
            return [];
        }

        $descriptors = [];
        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($paths) as $className => $reflection) {
            if (!is_a($className, AdminInterface::class, true)) {
                continue;
            }

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            $attributes = ClassReflection::getClassAttributes($className, AsAdmin::class);
            if ([] === $attributes) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            if (!$attribute instanceof AsAdmin) {
                continue;
            }

            $descriptor = self::buildDescriptor($className, $attribute);
            if (isset($descriptors[$descriptor['alias']])) {
                throw new \LogicException(sprintf(
                    'The easy-crud resource "%s" is declared twice with #[AsAdmin], on "%s" and on "%s". ' .
                    'A resource has a single Admin: remove one of the attributes, or give it another alias.',
                    $descriptor['alias'],
                    $descriptors[$descriptor['alias']]['admin'],
                    $className,
                ));
            }

            $descriptors[$descriptor['alias']] = $descriptor;
        }

        return array_values($descriptors);
    }

    /**
     * @param class-string<AdminInterface> $adminClass
     *
     * @return ResourceDescriptor
     */
    private static function buildDescriptor(string $adminClass, AsAdmin $attribute): array
    {
        /** @var class-string $resourceClass */
        $resourceClass = AdminDeclarationResolver::resolveResourceClass($adminClass);
        $grid = AdminDeclarationResolver::resolveGridName($adminClass);
        $alias = self::resolveAlias($adminClass, $attribute);
        $controller = $attribute->controller ?? self::DEFAULT_CONTROLLER;

        // --- Registry entry (equivalent to sylius_resource.resources.<alias>) ---
        $classes = [
            'model' => $resourceClass,
            'controller' => $controller,
            'form' => $adminClass,
        ];
        $repository = $attribute->repository ?? self::resolveRepository($resourceClass);
        if (null !== $repository) {
            $classes['repository'] = $repository;
        }

        $registry = [
            'driver' => 'doctrine/orm',
            'classes' => $classes,
        ];

        if (is_a($resourceClass, TranslatableInterface::class, true) && method_exists($resourceClass, 'getTranslationClass')) {
            /** @var callable(): string $getTranslationClass */
            $getTranslationClass = [$resourceClass, 'getTranslationClass'];
            $translationClass = $getTranslationClass();
            // Same as the YAML generated by the maker for a translatable model (Sylius 2.3).
            $registry['classes']['factory'] = TranslatableFactory::class;
            $registry['translation'] = [
                'classes' => [
                    'model' => $translationClass,
                    'controller' => $controller,
                    'form' => $adminClass,
                ],
            ];
        }

        $registry = self::deepMerge($registry, $attribute->resource, true);

        // --- Routing config (equivalent to the resource: block) ---
        $config = [
            'alias' => $alias,
            'section' => $attribute->section,
            'templates' => $attribute->templates,
            'redirect' => $attribute->redirect,
            'grid' => $grid,
            'form' => [
                'type' => $adminClass,
                'options' => ['context' => '$context'],
            ],
            'vars' => self::buildVars($attribute),
        ];

        // Only emit "permission" when explicitly set, so a migrated resource keeps the
        // legacy behaviour (omitted => Sylius normalizes to false) instead of silently
        // turning on app.<resource>.<action> permission checks.
        if (null !== $attribute->permission) {
            $config['permission'] = $attribute->permission;
        }
        if (null !== $attribute->path) {
            $config['path'] = $attribute->path;
        }
        if ([] !== $attribute->only && [] !== $attribute->except) {
            throw new \LogicException(sprintf(
                'The easy-crud admin "%s" sets both #[AsAdmin(only:)] and #[AsAdmin(except:)]: the Sylius routing accepts ' .
                'one of them only. Keep "only" (the actions to generate) or "except" (the actions to drop).',
                $adminClass,
            ));
        }
        self::assertKnownActions($adminClass, 'only', $attribute->only);
        self::assertKnownActions($adminClass, 'except', $attribute->except);
        if ([] !== $attribute->except) {
            $config['except'] = $attribute->except;
        }
        if ([] !== $attribute->only) {
            $config['only'] = $attribute->only;
        }

        return [
            'alias' => $alias,
            'admin' => $adminClass,
            'registry' => $registry,
            'routing' => [
                'prefix' => $attribute->prefix,
                'config' => $config,
            ],
        ];
    }

    /**
     * The `sylius.resource` route loader silently ignores an unknown action: a typo would drop or keep a route.
     *
     * @param list<string> $actions
     */
    private static function assertKnownActions(string $adminClass, string $argument, array $actions): void
    {
        foreach ($actions as $action) {
            if (!in_array($action, self::ACTIONS, true)) {
                throw new \LogicException(sprintf(
                    'The easy-crud admin "%s" lists the unknown action "%s" in #[AsAdmin(%s:)]. Valid actions: "%s".',
                    $adminClass,
                    $action,
                    $argument,
                    implode('", "', self::ACTIONS),
                ));
            }
        }
    }

    /**
     * The alias of the resource an Admin targets: the explicit #[AsAdmin(alias:)], otherwise the one of the
     * #[AsResource] declared on its model.
     *
     * @param class-string<AdminInterface> $adminClass
     */
    public static function resolveAlias(string $adminClass, AsAdmin $attribute): string
    {
        if (null !== $attribute->alias) {
            return $attribute->alias;
        }

        /** @var class-string $resourceClass */
        $resourceClass = AdminDeclarationResolver::resolveResourceClass($adminClass);

        return self::resolveResourceAlias($resourceClass, $adminClass);
    }

    /**
     * Default grid name of an Admin: the prefix of the routes Sylius generates for the resource,
     * `<application>_<section>_<name>` (alias "app.book" in section "admin" => "app_admin_book").
     *
     * @param class-string<AdminInterface> $adminClass
     */
    public static function deriveGridName(string $adminClass, AsAdmin $attribute): string
    {
        [$applicationName, $resourceName] = array_pad(explode('.', self::resolveAlias($adminClass, $attribute), 2), 2, '');

        return implode('_', array_filter([$applicationName, $attribute->section, $resourceName], static fn (string $part): bool => '' !== $part));
    }

    /**
     * Reads the alias from the native #[AsResource] on the model, mirroring
     * SyliusResourceExtension::getResourceAlias(): the explicit alias wins, otherwise
     * it is derived from <applicationName>.<short name without "Resource" suffix>.
     *
     * @param class-string $resourceClass
     * @param class-string $adminClass
     */
    private static function resolveResourceAlias(string $resourceClass, string $adminClass): string
    {
        $attributes = ClassReflection::getClassAttributes($resourceClass, AsResource::class);
        if ([] === $attributes) {
            throw new \LogicException(sprintf(
                'easy-crud admin "%s" references resourceClass "%s" which is not declared as a Sylius resource. ' .
                'Add #[\Sylius\Resource\Metadata\AsResource] on it (or set an explicit "alias" on #[AsAdmin]).',
                $adminClass,
                $resourceClass,
            ));
        }

        /** @var AsResource $resource */
        $resource = $attributes[0]->newInstance();
        $metadata = $resource->toMetadata();

        $alias = $metadata->getAlias();
        if (null !== $alias) {
            return $alias;
        }

        $applicationName = $metadata->getApplicationName() ?? self::DEFAULT_APPLICATION_NAME;
        $shortName = (new \ReflectionClass($resourceClass))->getShortName();
        if (str_ends_with($shortName, 'Resource')) {
            $shortName = substr($shortName, 0, -\strlen('Resource'));
        }

        return u($applicationName)->snake()->toString() . '.' . u($shortName)->snake()->toString();
    }

    /**
     * Builds the per-action vars, mirroring the maker-generated block, then
     * deep-merges the attribute's free-form vars on top.
     *
     * @return array<string, mixed>
     */
    private static function buildVars(AsAdmin $attribute): array
    {
        if (!$attribute->defaultVars) {
            return self::deepMerge(self::explicitVars($attribute), $attribute->vars);
        }

        $prefix = self::DEFAULT_TRANSLATION_PREFIX;

        $all = [
            'icon' => $attribute->icon ?? 'file',
            'subheader' => $attribute->subheader ?? $prefix . '.admin.ui.default.subheader',
            'breadcrumb' => $attribute->breadcrumb ?? $prefix . '.admin.ui.default.index',
            'templates' => [
                'form' => '@SyliusEasyCrudPlugin\\crud\\form\\_form.html.twig',
            ],
        ];

        $vars = [
            'all' => $all,
            'index' => ['header' => $attribute->header ?? $prefix . '.admin.ui.default.index'],
            'create' => ['header' => $attribute->header ?? $prefix . '.admin.ui.default.create'],
            'update' => [
                'header' => $attribute->header ?? $prefix . '.admin.ui.default.update',
                'redirect' => [
                    'route' => 'update',
                    'parameters' => ['context' => '$context', 'id' => '$id'],
                ],
                // read by the Sylius 2 index links of the update page (breadcrumb, back button): no id there
                'route' => [
                    'parameters' => ['context' => '$context'],
                ],
            ],
        ];

        return self::deepMerge($vars, $attribute->vars);
    }

    /**
     * The vars set through the dedicated arguments only, without any derived default.
     *
     * @return array<string, mixed>
     */
    private static function explicitVars(AsAdmin $attribute): array
    {
        $vars = [];
        foreach (['icon' => $attribute->icon, 'subheader' => $attribute->subheader, 'breadcrumb' => $attribute->breadcrumb] as $key => $value) {
            if (null !== $value) {
                $vars['all'][$key] = $value;
            }
        }
        if (null !== $attribute->header) {
            foreach (['index', 'create', 'update'] as $action) {
                $vars[$action]['header'] = $attribute->header;
            }
        }

        return $vars;
    }

    /**
     * @param class-string $resourceClass
     *
     * @return class-string|null
     */
    private static function resolveRepository(string $resourceClass): ?string
    {
        foreach (ClassReflection::getClassAttributes($resourceClass, Entity::class) as $attribute) {
            $arguments = $attribute->getArguments();
            $repository = $arguments['repositoryClass'] ?? $arguments[0] ?? null;
            // A plain Doctrine repository (e.g. a ServiceEntityRepository) cannot serve as the Sylius resource
            // repository class: leave the Sylius default, whose service is built through the entity manager
            // (getRepository()), i.e. is the Doctrine repository of the entity itself.
            if (is_string($repository) && is_a($repository, RepositoryInterface::class, true)) {
                return $repository;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     * @param bool                 $nullRemoves Whether a null override removes the key (e.g. the derived
     *                                          `translation` sub-resource of a translatable model)
     *
     * @return array<string, mixed>
     */
    private static function deepMerge(array $base, array $override, bool $nullRemoves = false): array
    {
        foreach ($override as $key => $value) {
            if ($nullRemoves && null === $value) {
                unset($base[$key]);

                continue;
            }
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                /** @var array<string, mixed> $baseValue */
                $baseValue = $base[$key];
                /** @var array<string, mixed> $overrideValue */
                $overrideValue = $value;
                $base[$key] = self::deepMerge($baseValue, $overrideValue, $nullRemoves);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
