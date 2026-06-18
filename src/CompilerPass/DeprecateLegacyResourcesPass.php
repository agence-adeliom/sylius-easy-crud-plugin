<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Sylius\Resource\Model\TranslationInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Triggers a deprecation, at container build time, for every easy-crud resource
 * still declared the legacy way (sylius_resource + `type: sylius.resource`) — so
 * that upgrading users see what to migrate to #[AsEasyCrudAdmin], even when they
 * never run the maker.
 *
 * Resources declared through #[AsEasyCrudAdmin] are excluded (their aliases are
 * recorded by the extension during prepend()).
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

        $declaredViaAttribute = $container->hasParameter('sylius_easy_crud.attributes.declared_aliases')
            ? array_flip((array) $container->getParameter('sylius_easy_crud.attributes.declared_aliases'))
            : [];

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

            if (!self::isEasyCrudResource($configuration['classes'])) {
                continue;
            }

            trigger_deprecation(
                'agence-adeliom/sylius-easy-crud-plugin',
                '2.1',
                'Declaring the easy-crud resource "%s" through legacy YAML (sylius_resource + "type: sylius.resource") ' .
                'is deprecated and will be removed in 3.0. Declare it with #[AsEasyCrudAdmin] on its Admin class instead',
                (string) $alias,
            );
        }
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
