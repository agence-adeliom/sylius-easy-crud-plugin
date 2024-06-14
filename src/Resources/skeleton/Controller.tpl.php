<?php declare(strict_types=1);
if (isset($namespace, $entity, $class_name)) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;

class <?= $class_name ?> extends SyliusCrudResourceController
{}
<?php } ?>
