<?php

declare(strict_types=1);

use Adeliom\SyliusEasyCrudPlugin\Maker\CreateEasyCrud;
use Adeliom\SyliusEasyCrudPlugin\Maker\CreateEntity;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    $services->set(CreateEasyCrud::class)
        ->tag('maker.command')
        ->public()
        ->args([
            service('doctrine'),
            service('parameter_bag'),
        ]);

    $services->set(CreateEntity::class)
        ->tag('maker.command')
        ->public()
        ->args([
            service('doctrine'),
            service('parameter_bag'),
        ]);
};
