<?php declare(strict_types=1);
if (isset($entity, $repository, $class_name)) { ?>
sylius_resource:
    resources:
        app.<?= strtolower($entity->getShortName()) ?>:
            driver: doctrine/orm # You can use also different driver here
            classes:
                model: <?php echo $entity->getName();
    echo "\n"; ?>
                repository: <?= $repository->getName() . "\n" ?>
                #controller: App\Controller\Admin\<?= $entity->getShortName() ?>Controller
                form: App\Admin\<?= $class_name ?><?php } ?>
