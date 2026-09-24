<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Context\Setup\PostContext;
use Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Context\Ui\Admin\ManagingPostsContext;
use Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post\CreatePage;
use Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post\IndexPage;
use Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Page\Admin\Post\UpdatePage;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->public()
            ->autowire()
            ->autoconfigure(false);

    $services->set('tests.adeliom.sylius_easy_crud_plugin.behat.context.setup.post', PostContext::class)
        ->args([
            service('tests_adeliom_sylius_easy_crud_plugin.factory.tests_adeliom_sylius_easy_crud_plugin_entity_post'),
            service('tests_adeliom_sylius_easy_crud_plugin.repository.tests_adeliom_sylius_easy_crud_plugin_entity_post'),
            service('sylius.factory.taxon'),
            service('sylius.repository.taxon'),
            service('sylius.factory.product'),
            service('sylius.repository.product'),
            service('doctrine.orm.entity_manager'),
        ]);

    $services->set('tests.adeliom.sylius_easy_crud_plugin.behat.context.ui.admin.managing_posts', ManagingPostsContext::class)
        ->args([
            service('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.index'),
            service('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.create'),
            service('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.update'),
        ]);

    // Pages
    $services->set('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.index', IndexPage::class)
        ->parent('sylius.behat.symfony_page');
    $services->set('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.create', CreatePage::class)
        ->parent('sylius.behat.symfony_page');
    $services->set('tests.adeliom.sylius_easy_crud_plugin.behat.page.admin.post.update', UpdatePage::class)
        ->parent('sylius.behat.symfony_page');
};
