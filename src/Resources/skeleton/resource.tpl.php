sylius_resource:
    resources:
        app.<?= $entity->getShortName() ?>:
            driver: doctrine/orm # You can use also different driver here
            classes:
                model: <?php echo $entity->getName();
        echo "\n"; ?>
                repository: <?= $repository->getName() ?>
#                controller: App\Controller\Admin\<?= $entity->getShortName() ?>Controller
                form: App\Admin\<?= $class_name ?>
