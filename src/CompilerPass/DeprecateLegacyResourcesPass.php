<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Composer\Autoload\ClassLoader;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Triggers a deprecation, at container build time, for every easy-crud resource
 * still declared the legacy way (sylius_resource + `type: sylius.resource`) — so
 * that upgrading users see what to migrate to #[AsAdmin], even when they
 * never run the maker.
 *
 * Resources declared through #[AsAdmin] are excluded (their aliases are
 * recorded by the extension during prepend()), as are the resources whose model and
 * Admin both live outside the application: those are declared by another plugin
 * (e.g. happy-cms), and the application could not migrate them. A class belongs to the
 * application when its (real) file is under the (real) project directory and outside
 * Composer's vendor directory: a plugin installed from a symlinked path repository
 * (e.g. mounted in /var/www/plugins) is outside the project directory.
 */
final class DeprecateLegacyResourcesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('sylius.resources')) {
            return;
        }

        /** @var array<string, mixed> $resources */
        $resources = $container->getParameter('sylius.resources');

        /** @var list<string> $declaredAliases */
        $declaredAliases = $container->hasParameter('sylius_easy_crud.attributes.declared_aliases')
            ? $container->getParameter('sylius_easy_crud.attributes.declared_aliases')
            : [];
        $declaredViaAttribute = array_flip($declaredAliases);

        $vendorDirectory = self::vendorDirectory();
        $projectDirectory = self::projectDirectory($container);

        foreach ($resources as $alias => $configuration) {
            if (isset($declaredViaAttribute[$alias])) {
                continue;
            }
            if (!is_array($configuration) || !isset($configuration['classes']) || !is_array($configuration['classes'])) {
                continue;
            }

            // Skip translation sub-resources: they are auto-registered from their parent
            // and migrate along with it, so deprecating them separately is just noise.
            $model = $configuration['classes']['model'] ?? null;
            if (is_string($model) && class_exists($model) && is_a($model, TranslationInterface::class, true)) {
                continue;
            }

            /** @var array<string, mixed> $classes */
            $classes = $configuration['classes'];
            if (!self::isEasyCrudResource($classes)) {
                continue;
            }
            if (!self::isDeclaredByTheApplication($classes, $projectDirectory, $vendorDirectory)) {
                continue;
            }

            trigger_deprecation(
                'agence-adeliom/sylius-easy-crud-plugin',
                '2.3.2',
                'Declaring the easy-crud resource "%s" through legacy YAML (sylius_resource + "type: sylius.resource") ' .
                'is deprecated and will be removed in 3.0. Set "sylius_easy_crud.attributes.enabled: true" and declare ' .
                'it with #[AsAdmin] on its Admin class instead: see "How to remove the deprecations" in ' .
                'vendor/agence-adeliom/sylius-easy-crud-plugin/UPGRADE.md.',
                (string) $alias,
            );
        }
    }

    /**
     * Whether the model or the Admin (form) of the resource is a class of the application, i.e. declared under the
     * project directory (when known) and outside Composer's vendor directory.
     *
     * @param array<string, mixed> $classes
     */
    private static function isDeclaredByTheApplication(array $classes, ?string $projectDirectory, ?string $vendorDirectory): bool
    {
        if (null === $projectDirectory && null === $vendorDirectory) {
            return true;
        }

        foreach (['model', 'form'] as $key) {
            $class = $classes[$key] ?? null;
            if (!is_string($class) || !class_exists($class)) {
                continue;
            }

            $file = (new \ReflectionClass($class))->getFileName();
            if (false === $file) {
                continue;
            }
            // PHP reports the real path of a file loaded through a symlink
            $file = realpath($file) ?: $file;

            if (null !== $projectDirectory && !self::isInDirectory($file, $projectDirectory)) {
                continue;
            }
            if (null !== $vendorDirectory && self::isInDirectory($file, $vendorDirectory)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private static function isInDirectory(string $file, string $directory): bool
    {
        return str_starts_with($file, rtrim($directory, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR);
    }

    private static function projectDirectory(ContainerBuilder $container): ?string
    {
        if (!$container->hasParameter('kernel.project_dir')) {
            return null;
        }

        $projectDirectory = $container->getParameter('kernel.project_dir');
        if (!is_string($projectDirectory)) {
            return null;
        }

        $realProjectDirectory = realpath($projectDirectory);

        return false === $realProjectDirectory ? null : $realProjectDirectory;
    }

    private static function vendorDirectory(): ?string
    {
        if (!class_exists(ClassLoader::class)) {
            return null;
        }

        $file = (new \ReflectionClass(ClassLoader::class))->getFileName();
        if (false === $file) {
            return null;
        }

        $vendorDirectory = realpath(\dirname($file, 2));

        return false === $vendorDirectory ? null : $vendorDirectory;
    }

    /**
     * A resource is considered an easy-crud one when its controller is (a subclass of)
     * SyliusCrudResourceController, or its form is an easy-crud Admin.
     *
     * @param array<string, mixed> $classes
     */
    private static function isEasyCrudResource(array $classes): bool
    {
        $controller = $classes['controller'] ?? null;
        if (is_string($controller) && class_exists($controller) && is_a($controller, SyliusCrudResourceController::class, true)) {
            return true;
        }

        $form = $classes['form'] ?? null;
        if (is_string($form) && class_exists($form) && is_a($form, AdminInterface::class, true)) {
            return true;
        }

        return false;
    }
}
