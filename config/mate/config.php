<?php

declare(strict_types=1);

use Adeliom\SyliusEasyCrudPlugin\Mate\Tool\InspectAdmin;
use Adeliom\SyliusEasyCrudPlugin\Mate\Tool\ListAdmins;
use Sylius\MateExtension\Kernel\HostKernelProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

// Loaded by Symfony AI Mate (not by the application): the tools read the application through the host kernel booted
// by sylius/sylius-mate-extension, which must be installed and enabled in mate/extensions.php.
return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    foreach ([ListAdmins::class, InspectAdmin::class] as $tool) {
        $services->set($tool)
            ->args([service(HostKernelProvider::class)])
            ->public()
        ;
    }
};
