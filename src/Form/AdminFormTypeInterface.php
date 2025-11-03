<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Asset;

interface AdminFormTypeInterface
{
    /**
     * Declare here the assets that make your admin field working as expected
     *
     * @return array<string, array<int, Asset|string>>
     */
    public static function configureAdminAssets(): array;

    /**
     * Declare here the form themes that make your admin field working as expected
     *
     * @return string[]
     */
    public static function configureAdminFormThemes(): array;
}
