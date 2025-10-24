<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
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
            ->addTag('sylius_easy_crud');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
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
