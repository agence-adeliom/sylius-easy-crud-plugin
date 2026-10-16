<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;

/**
 * Single place that reads the resource class and the grid name of an easy-crud Admin, shared by the
 * container (RegisterAdminPass: data class of the form), the attribute resources (EasyCrudResourceFactory:
 * model and grid of the routes) and the Admin itself at runtime (AbstractAdmin: grid, resource context).
 *
 * Rule:
 *  - resource class: `#[AsAdmin(resourceClass:)]` when set, otherwise `getEntityFqcn()`;
 *  - grid name: always `getName()`, which Sylius uses to register and find the grid. `AbstractAdmin::getName()`
 *    returns `#[AsAdmin(grid:)]`, or derives it from the resource alias;
 *  - when the attribute sets a value and `getEntityFqcn()` / `getName()` is overridden, by the Admin itself or by a
 *    parent Admin it extends (an application Admin extending a vendor one), the method must return the same value:
 *    otherwise the container compilation fails, instead of registering the grid / model under one value while the
 *    public method returns the other one at runtime. An inherited method is never silently ignored: the Admin must
 *    override it.
 */
final class AdminDeclarationResolver
{
    /** @var array<class-string, AsAdmin|null> */
    private static array $attributes = [];

    /** @var array<class-string, string> */
    private static array $resourceClasses = [];

    private function __construct()
    {
    }

    /**
     * The #[AsAdmin] declared on the class itself (attributes are not inherited), if any.
     *
     * @param class-string $adminClass
     */
    public static function attribute(string $adminClass): ?AsAdmin
    {
        if (!array_key_exists($adminClass, self::$attributes)) {
            $attributes = (new \ReflectionClass($adminClass))->getAttributes(AsAdmin::class);
            self::$attributes[$adminClass] = [] === $attributes ? null : $attributes[0]->newInstance();
        }

        return self::$attributes[$adminClass];
    }

    /**
     * @param class-string $adminClass
     *
     * @throws \LogicException when the attribute and getEntityFqcn() declared on the Admin diverge
     */
    public static function resolveResourceClass(string $adminClass): string
    {
        return self::$resourceClasses[$adminClass] ??= self::doResolveResourceClass($adminClass);
    }

    /**
     * @param class-string $adminClass
     *
     * @throws \LogicException when #[AsAdmin(grid:)] and getName() diverge
     */
    public static function resolveGridName(string $adminClass): string
    {
        if (!method_exists($adminClass, 'getName')) {
            throw new \LogicException(sprintf('The easy-crud admin "%s" must define getName().', $adminClass));
        }

        /** @var string $name */
        $name = $adminClass::getName();
        $grid = self::attribute($adminClass)?->grid;

        if (null !== $grid && $grid !== $name) {
            $declaringClass = self::overridingClass($adminClass, 'getName');

            if (null !== $declaringClass && $declaringClass !== $adminClass) {
                throw new \LogicException(sprintf(
                    'The easy-crud admin "%1$s" sets the grid name "%2$s" with #[AsAdmin(grid:)], but getName() inherited ' .
                    'from "%3$s" returns "%4$s". Sylius registers the grid under getName(). Override getName() in "%1$s" ' .
                    'to return "%2$s", or remove the "grid" argument to keep "%4$s".',
                    $adminClass,
                    $grid,
                    $declaringClass,
                    $name,
                ));
            }

            throw new \LogicException(sprintf(
                'The easy-crud admin "%s" declares two grid names: getName() returns "%s" but #[AsAdmin(grid: "%s")]. ' .
                'Sylius registers the grid under getName(): remove the "grid" argument, or remove the getName() method, ' .
                'or make them return the same value.',
                $adminClass,
                $name,
                $grid,
            ));
        }

        return $name;
    }

    /**
     * Validates, at container build time, that the attribute does not contradict the overridden methods.
     *
     * @param class-string $adminClass
     *
     * @throws \LogicException
     */
    public static function assertConsistent(string $adminClass): void
    {
        self::resolveResourceClass($adminClass);

        // getName() is only compared when the attribute sets a grid: without it, the derived name may need a
        // resource alias, which only exists once the attribute discovery is enabled.
        if (null !== self::attribute($adminClass)?->grid) {
            self::resolveGridName($adminClass);
        }
    }

    /**
     * @param class-string $adminClass
     */
    private static function doResolveResourceClass(string $adminClass): string
    {
        $resourceClass = self::attribute($adminClass)?->resourceClass;

        if (null === $resourceClass) {
            if (!method_exists($adminClass, 'getEntityFqcn')) {
                throw new \LogicException(sprintf('The easy-crud admin "%s" must define getEntityFqcn() or #[AsAdmin(resourceClass: ...)].', $adminClass));
            }

            /** @var string $fromMethod */
            $fromMethod = $adminClass::getEntityFqcn();

            return ltrim($fromMethod, '\\');
        }

        $resourceClass = ltrim($resourceClass, '\\');
        $declaringClass = self::overridingClass($adminClass, 'getEntityFqcn');

        if (null !== $declaringClass) {
            /** @var string $fromMethod */
            $fromMethod = $adminClass::getEntityFqcn();
            $fromMethod = ltrim($fromMethod, '\\');

            if ($fromMethod !== $resourceClass && $declaringClass !== $adminClass) {
                throw new \LogicException(sprintf(
                    'The easy-crud admin "%1$s" sets the resource class "%2$s" with #[AsAdmin(resourceClass:)], but ' .
                    'getEntityFqcn() inherited from "%3$s" returns "%4$s": getEntityFqcn() is public and would keep ' .
                    'returning the model of the parent Admin. Override getEntityFqcn() in "%1$s" to return the same ' .
                    'class (`return %2$s::class;`), or remove the "resourceClass" argument.',
                    $adminClass,
                    $resourceClass,
                    $declaringClass,
                    $fromMethod,
                ));
            }

            if ($fromMethod !== $resourceClass) {
                throw new \LogicException(sprintf(
                    'The easy-crud admin "%s" declares two resource classes: getEntityFqcn() returns "%s" but ' .
                    '#[AsAdmin(resourceClass: %s::class)]. Remove one of them, or make them return the same class.',
                    $adminClass,
                    $fromMethod,
                    $resourceClass,
                ));
            }
        }

        return $resourceClass;
    }

    /**
     * The class declaring the method when it overrides the default of AbstractAdmin (the Admin itself or one of its
     * parents), null when the method is the one of AbstractAdmin, which reads the attribute.
     *
     * @param class-string $class
     */
    private static function overridingClass(string $class, string $method): ?string
    {
        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->hasMethod($method)) {
            return null;
        }

        $declaringClass = $reflectionClass->getMethod($method)->getDeclaringClass()->getName();

        return AbstractAdmin::class === $declaringClass ? null : $declaringClass;
    }
}
