<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Doctrine\ORM\Mapping\Entity;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Reflection\ClassReflection;
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
 *     registry: array<string, mixed>,
 *     routing: array{prefix: string, config: array<string, mixed>}
 * }
 */
final class EasyCrudResourceFactory
{
    public const DEFAULT_APPLICATION_NAME = 'app';

    public const DEFAULT_CONTROLLER = SyliusCrudResourceController::class;

    public const DEFAULT_TRANSLATION_PREFIX = 'sylius_easy_crud_plugin';

    /**
     * @param list<string> $paths Directories holding the easy-crud Admin classes.
     *
     * @return list<ResourceDescriptor>
     */
    public static function build(array $paths): array
    {
        if ([] === $paths) {
            return [];
        }

        $descriptors = [];
        foreach (ClassReflection::getResourcesByPaths($paths) as $className) {
            if (!class_exists($className) || !is_a($className, AdminInterface::class, true)) {
                continue;
            }

            $reflection = new \ReflectionClass($className);
            if ($reflection->isAbstract()) {
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

            $descriptors[] = self::buildDescriptor($className, $attribute);
        }

        return $descriptors;
    }

    /**
     * @param class-string<AdminInterface> $adminClass
     *
     * @return ResourceDescriptor
     */
    private static function buildDescriptor(string $adminClass, AsAdmin $attribute): array
    {
        /** @var class-string $resourceClass */
        $resourceClass = ltrim((string) ($attribute->resourceClass ?? $adminClass::getEntityFqcn()), '\\');
        $grid = $attribute->grid ?? $adminClass::getName();
        $alias = $attribute->alias ?? self::resolveResourceAlias($resourceClass, $adminClass);
        $controller = $attribute->controller ?? self::DEFAULT_CONTROLLER;

        // --- Registry entry (equivalent to sylius_resource.resources.<alias>) ---
        $classes = [
            'model' => $resourceClass,
            'controller' => $controller,
            'form' => $adminClass,
        ];
        $repository = self::resolveRepository($resourceClass);
        if (null !== $repository) {
            $classes['repository'] = $repository;
        }

        $registry = [
            'driver' => 'doctrine/orm',
            'classes' => $classes,
        ];

        if (is_a($resourceClass, TranslatableInterface::class, true) && method_exists($resourceClass, 'getTranslationClass')) {
            /** @var string $translationClass */
            $translationClass = call_user_func([$resourceClass, 'getTranslationClass']);
            $registry['translation'] = [
                'classes' => [
                    'model' => $translationClass,
                    'controller' => $controller,
                    'form' => $adminClass,
                ],
            ];
        }

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
        if ([] !== $attribute->except) {
            $config['except'] = array_values($attribute->except);
        }
        if ([] !== $attribute->only) {
            $config['only'] = array_values($attribute->only);
        }

        return [
            'alias' => $alias,
            'registry' => $registry,
            'routing' => [
                'prefix' => $attribute->prefix,
                'config' => $config,
            ],
        ];
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
                'route' => [
                    'parameters' => ['context' => '$context', 'id' => '$id'],
                ],
            ],
        ];

        return self::deepMerge($vars, $attribute->vars);
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
            if (is_string($repository) && '' !== $repository) {
                /** @var class-string $repositoryClass */
                $repositoryClass = $repository;

                return $repositoryClass;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private static function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
