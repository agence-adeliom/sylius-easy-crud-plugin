<?php

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class
AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $newSubmenu = $menu
            ->addChild('custom')
            ->setLabel('Custom')
        ;

        $newSubmenu
            ->addChild('entity_test', ['route' => 'tests_adeliom_sylius_easy_crud_plugin_admin_tests_adeliom_sylius_easy_crud_plugin_entity_post_index'])
            ->setLabel('Entity test')
            ->setLabelAttribute('icon', 'file')
        ;

        $children = $menu->getChildren();
        $custom = $children['custom'];
        unset($children['custom']);
        $menu->reorderChildren(
            array_keys([
                'custom' => $custom,
            ] + $children));
    }
}
