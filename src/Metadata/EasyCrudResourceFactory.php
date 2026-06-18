<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Doctrine\ORM\Mapping\Entity;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Reflection\ClassReflection;

/**
 * Scans the configured paths for Admin classes carrying #[AsEasyCrudAdmin] and
 * translates each one into the legacy Sylius resource configuration and routing
 * configuration that the equivalent config/routes.yaml + sylius_resource.yaml
 * blocks used to provide.
 *
 * Everything is read straight from the Admin: the entity comes from
 * Admin::getEntityFqcn(), the grid from Admin::getName(), and the form type IS
 * the Admin class itself. No entity => Admin resolution is required.
 *
 * Pure helper (no DI): it runs at container build time, from the bundle
 * extension's prepend(), where service tags are not yet available.
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

            $attributes = ClassReflection::getClassAttributes($className, AsEasyCrudAdmin::class);
            if ([] === $attributes) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            if (!$attribute instanceof AsEasyCrudAdmin) {
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
    private static function buildDescriptor(string $adminClass, AsEasyCrudAdmin $attribute): array
    {
        /** @var class-string $entityClass */
        $entityClass = $adminClass::getEntityFqcn();
        $grid = $adminClass::getName();
        $alias = $attribute->alias ?? sprintf('%s.%s', self::DEFAULT_APPLICATION_NAME, self::snake(self::shortName($entityClass)));
        $controller = $attribute->controller ?? self::DEFAULT_CONTROLLER;

        // --- Registry entry (equivalent to sylius_resource.resources.<alias>) ---
        $classes = [
            'model' => $entityClass,
            'controller' => $controller,
            'form' => $adminClass,
        ];
        $repository = self::resolveRepository($entityClass);
        if (null !== $repository) {
            $classes['repository'] = $repository;
        }

        $registry = [
            'driver' => 'doctrine/orm',
            'classes' => $classes,
        ];

        if (is_a($entityClass, TranslatableInterface::class, true) && method_exists($entityClass, 'getTranslationClass')) {
            /** @var string $translationClass */
            $translationClass = call_user_func([$entityClass, 'getTranslationClass']);
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
            'permission' => $attribute->permission,
            'grid' => $grid,
            'form' => [
                'type' => $adminClass,
                'options' => ['context' => '$context'],
            ],
            'vars' => self::buildVars($attribute),
        ];

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
     * Builds the per-action vars, mirroring the maker-generated block, then
     * deep-merges the attribute's free-form vars on top.
     *
     * @return array<string, mixed>
     */
    private static function buildVars(AsEasyCrudAdmin $attribute): array
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
     * @param class-string $entityClass
     */
    private static function resolveRepository(string $entityClass): ?string
    {
        foreach (ClassReflection::getClassAttributes($entityClass, Entity::class) as $attribute) {
            $arguments = $attribute->getArguments();
            $repository = $arguments['repositoryClass'] ?? $arguments[0] ?? null;
            if (is_string($repository) && '' !== $repository) {
                return $repository;
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

    /**
     * @param class-string $className
     */
    private static function shortName(string $className): string
    {
        $position = strrpos($className, '\\');

        return false === $position ? $className : substr($className, $position + 1);
    }

    private static function snake(string $value): string
    {
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);

        return mb_strtolower($snake ?? $value);
    }
}
