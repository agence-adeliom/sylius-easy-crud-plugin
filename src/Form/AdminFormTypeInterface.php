<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Form;

interface AdminFormTypeInterface
{
    /**
     * Declare here the assets that make your admin field working as expected
     *
     * @return array<string, string[]>
     */
    public static function configureAdminAssets(): array;

    /**
     * Declare here the form themes that make your admin field working as expected
     *
     * @return string[]
     */
    public static function configureAdminFormThemes(): array;
}
