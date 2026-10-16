<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Mate;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Reads the easy-crud resources of the host application from its booted container, for the Mate tools: the Sylius
 * resource registry (`sylius.resources`), the aliases declared with #[AsAdmin], the router and the form factory.
 *
 * @phpstan-type AdminDescription array{
 *     alias: string,
 *     admin: class-string<AdminInterface>,
 *     model: string|null,
 *     controller: string|null,
 *     declaration: 'attribute'|'yaml',
 *     grid: string,
 *     routes: list<array{name: string, action: string, path: string, methods: list<string>}>
 * }
 */
final class AdminReader
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @return list<AdminDescription>
     */
    public function all(?string $aliasPrefix = null): array
    {
        $declaredAliases = $this->parameter('sylius_easy_crud.attributes.declared_aliases');

        $resources = $this->resources();
        $translationAliases = $this->translationAliases($resources);

        $admins = [];
        foreach ($resources as $alias => $resource) {
            if (null !== $aliasPrefix && !str_starts_with($alias, $aliasPrefix)) {
                continue;
            }

            if (isset($translationAliases[$alias])) {
                continue;
            }

            $classes = \is_array($resource['classes'] ?? null) ? $resource['classes'] : [];
            $admin = $classes['form'] ?? null;
            if (!$this->isEasyCrudResource($classes) || !\is_string($admin) || !is_a($admin, AdminInterface::class, true)) {
                continue;
            }

            $admins[] = [
                'alias' => $alias,
                'admin' => $admin,
                'model' => \is_string($classes['model'] ?? null) ? $classes['model'] : null,
                'controller' => \is_string($classes['controller'] ?? null) ? $classes['controller'] : null,
                'declaration' => \in_array($alias, $declaredAliases, true) ? 'attribute' : 'yaml',
                'grid' => $this->gridName($admin),
                'routes' => $this->routes($alias),
            ];
        }

        usort($admins, static fn (array $a, array $b): int => $a['alias'] <=> $b['alias']);

        return $admins;
    }

    /**
     * @return AdminDescription|null
     */
    public function get(string $alias): ?array
    {
        foreach ($this->all($alias) as $admin) {
            if ($admin['alias'] === $alias) {
                return $admin;
            }
        }

        return null;
    }

    /**
     * The Admin service, as the form registry uses it, without building its form.
     *
     * @param class-string<AdminInterface> $admin
     */
    public function admin(string $admin): AdminInterface
    {
        $locator = $this->container->get(RegisterAdminPass::ADMIN_LOCATOR);
        \assert($locator instanceof ContainerInterface);

        $instance = $locator->get($admin);
        \assert($instance instanceof AdminInterface);

        return $instance;
    }

    /**
     * Builds the form of the Admin for a page ("new", "update"), the same way the easy-crud controller does.
     *
     * @param class-string<AdminInterface> $admin
     *
     * @return FormInterface<mixed>
     */
    public function createForm(string $admin, string $pageName): FormInterface
    {
        $formFactory = $this->container->get('form.factory');
        \assert($formFactory instanceof FormFactoryInterface);

        return $formFactory->create($admin, null, ['page_name' => $pageName]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resources(): array
    {
        if (!$this->container instanceof \Symfony\Component\DependencyInjection\ContainerInterface ||
            !$this->container->hasParameter('sylius.resources')) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $resources */
        $resources = $this->container->getParameter('sylius.resources');

        return $resources;
    }

    /**
     * The `<alias>_translation` sub-resources Sylius registers for the translatable resources: they inherit the
     * classes of their parent, its Admin included, but have neither grid nor routes.
     *
     * @param array<string, array<string, mixed>> $resources
     *
     * @return array<string, true>
     */
    private function translationAliases(array $resources): array
    {
        $aliases = [];
        foreach ($resources as $alias => $resource) {
            if (isset($resource['translation'])) {
                $aliases[$alias . '_translation'] = true;
            }
        }

        return $aliases;
    }

    /**
     * @return list<string>
     */
    private function parameter(string $name): array
    {
        if (!$this->container instanceof \Symfony\Component\DependencyInjection\ContainerInterface ||
            !$this->container->hasParameter($name)) {
            return [];
        }

        $value = $this->container->getParameter($name);

        return \is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * Same rule as DeprecateLegacyResourcesPass: the controller is easy-crud's, or the form is an easy-crud Admin.
     *
     * @param array<mixed> $classes
     */
    private function isEasyCrudResource(array $classes): bool
    {
        $controller = $classes['controller'] ?? null;
        if (\is_string($controller) && is_a($controller, SyliusCrudResourceController::class, true)) {
            return true;
        }

        $form = $classes['form'] ?? null;

        return \is_string($form) && is_a($form, AdminInterface::class, true);
    }

    /**
     * @param class-string<AdminInterface> $admin
     */
    private function gridName(string $admin): string
    {
        try {
            return $admin::getName();
        } catch (\Throwable $exception) {
            return sprintf('(unresolved: %s)', $exception->getMessage());
        }
    }

    /**
     * The routes served by the controller of the resource (`<application>.controller.<name>`), as generated by the
     * `sylius.resource` route loader for both declarations.
     *
     * @return list<array{name: string, action: string, path: string, methods: list<string>}>
     */
    private function routes(string $alias): array
    {
        if (!$this->container->has('router')) {
            return [];
        }

        $router = $this->container->get('router');
        \assert($router instanceof RouterInterface);

        [$application, $name] = explode('.', $alias, 2) + [1 => ''];
        $controllerPrefix = sprintf('%s.controller.%s::', $application, $name);

        $routes = [];
        foreach ($router->getRouteCollection() as $routeName => $route) {
            $controller = $route->getDefault('_controller');
            if (!\is_string($controller) || !str_starts_with($controller, $controllerPrefix)) {
                continue;
            }

            $routes[] = [
                'name' => $routeName,
                'action' => preg_replace('/Action$/', '', substr($controller, \strlen($controllerPrefix))) ?? '',
                'path' => $route->getPath(),
                'methods' => array_values($route->getMethods()),
            ];
        }

        return $routes;
    }
}
