<?php

declare(strict_types=1);

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\ChoiceMaskConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\DateTimeConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\EnumConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\ResourceChoiceFieldConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\SortableCollectionConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\TranslationConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Asset\AssetEasyCrudPackage;
use Adeliom\SyliusEasyCrudPlugin\Controller\OembedController;
use Adeliom\SyliusEasyCrudPlugin\Controller\PositionSortController;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldConfiguratorCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource\ResourceContextResolver;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\View\CrudViewBuilderFactory;
use Adeliom\SyliusEasyCrudPlugin\Form\ChoiceMaskType;
use Adeliom\SyliusEasyCrudPlugin\Form\FieldResourceTranslationsType;
use Adeliom\SyliusEasyCrudPlugin\Form\ImageType;
use Adeliom\SyliusEasyCrudPlugin\Form\ResourceChoiceType;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use Adeliom\SyliusEasyCrudPlugin\Intl\IntlFormatter;
use Adeliom\SyliusEasyCrudPlugin\Routing\EasyCrudAttributesRoutesLoader;
use Adeliom\SyliusEasyCrudPlugin\Services\AssetRenderer;
use Adeliom\SyliusEasyCrudPlugin\Services\PositionShifter;
use Adeliom\SyliusEasyCrudPlugin\Services\PositionShifterInterface;
use Adeliom\SyliusEasyCrudPlugin\Services\ResourceUpdatePermissionChecker;
use Adeliom\SyliusEasyCrudPlugin\Services\ResourceUpdatePermissionCheckerInterface;
use Adeliom\SyliusEasyCrudPlugin\Twig\CrudRenderExtension;
use Adeliom\SyliusEasyCrudPlugin\Twig\OembedExtension;
use Adeliom\SyliusEasyCrudPlugin\Twig\ResourcePropertyAccessorExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(OembedController::class)
        ->public();

    $services->set(PositionSortController::class)
        ->public();

    $services->set(PositionShifter::class);
    $services->alias(PositionShifterInterface::class, PositionShifter::class);

    $services->set(ResourceUpdatePermissionChecker::class);
    $services->alias(ResourceUpdatePermissionCheckerInterface::class, ResourceUpdatePermissionChecker::class);

    $services->set(SortableCollectionType::class)
        ->public();

    $services->set(SortableCollectionConfigurator::class)
        ->public();

    $services->set(ResourceChoiceFieldConfigurator::class)
        ->public();

    // $services->set(AssociationConfigurator::class)
    //     ->public();

    $services->set(ChoiceMaskType::class)
        ->public();

    $services->set(ChoiceMaskConfigurator::class)
        ->public();

    $services->set(EnumConfigurator::class)
        ->public();

    $services->set(TranslationConfigurator::class)
        ->args([service('sylius.translation_locale_provider')])
        ->public();

    $services->set('sylius.easycrud.intlformatter', IntlFormatter::class);

    $services->set(DateTimeConfigurator::class)
        ->args([service('sylius.easycrud.intlformatter')])
        ->public();

    $services->set(OembedExtension::class)
        ->public()
        ->tag('twig.extension');

    $services->alias('adeliom.sylius.twig.oembed_extension', OembedExtension::class)
        ->public();

    $services->set(ResourcePropertyAccessorExtension::class)
        ->public()
        ->args([service('property_accessor')])
        ->tag('twig.extension');

    $services->alias('adeliom.sylius.twig.resource_property_extension', ResourcePropertyAccessorExtension::class)
        ->public();

    $services->set(CrudRenderExtension::class)
        ->public()
        ->args([service('property_accessor')])
        ->tag('twig.extension');

    $services->alias('adeliom.sylius.twig.crud_render_extension', CrudRenderExtension::class)
        ->public();

    $services->set(FieldConfiguratorCollection::class)
        ->args([tagged_iterator('easy.fields.configurator')]);

    $services->alias('adeliom.sylius.field.configurator.collection', FieldConfiguratorCollection::class)
        ->public();

    $services->set('adeliom.sylius.form.type.field_resource_translations', FieldResourceTranslationsType::class)
        ->public()
        ->tag('form.type')
        ->args([
            service('sylius.translation_locale_provider'),
            service('property_accessor'),
        ]);

    $services->alias(FieldResourceTranslationsType::class, 'adeliom.sylius.form.type.field_resource_translations')
        ->public();

    $services->set('adeliom.sylius.form.type.resource_choice', ResourceChoiceType::class)
        ->public()
        ->tag('form.type')
        ->args([service('sylius.registry.resource_repository')]);

    $services->alias(ResourceChoiceType::class, 'adeliom.sylius.form.type.resource_choice')
        ->public();

    $services->set(CrudViewBuilderFactory::class)
        ->args([service('knp_menu.factory')]);

    $services->set(ResourceContextResolver::class)
        ->args([
            service('parameter_bag'),
            service('request_stack'),
            service('sylius.resource_controller.request_configuration_factory'),
        ]);

    $services->set('assets._package_sylius.easy_crud', AssetEasyCrudPackage::class)
        ->args([service('request_stack')])
        ->tag('assets.package', ['package' => 'sylius.easy_crud']);

    $services->set(ImageType::class)
        ->args([service('sylius.uploader.image')]);

    $services->set('adeliom.sylius.asset.renderer', AssetRenderer::class)
        ->public()
        ->args([
            service('event_dispatcher'),
            service('webpack_encore.tag_renderer'),
            service('assets.packages'),
        ]);

    $services->set(EasyCrudAttributesRoutesLoader::class)
        ->autoconfigure(false)
        ->args([
            param('sylius_easy_crud.attributes.routing'),
            service('sylius.routing.loader.resource'),
        ])
        ->tag('routing.route_loader');
};
