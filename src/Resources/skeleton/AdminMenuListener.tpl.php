<?php declare(strict_types=1);

if (isset($class_name, $route)) {
    ?>
<?= "<?php\n" ?>

namespace <?= $menu_namespace ?? 'App\Menu' ?>;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $newSubmenu = $menu
            ->addChild('custom')
            ->setLabel('Custom')
            ;

        $newSubmenu
            ->addChild('entity_test', ['route' => '<?= strtolower($route) ?>_index'])
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

<?php } ?>
