<?php declare(strict_types=1);

if (isset($class_name, $entity_name)) {
    ?>
<?= "<?php\n" ?>

namespace App\Repository;

use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use App\Entity\<?= $entity_name ?>;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @method <?= $entity_name ?>|null find($id, $lockMode = null, $lockVersion = null)
 * @method <?= $entity_name ?>|null findOneBy(array $criteria, array $orderBy = null)
 * @method <?= $entity_name ?>[]    findAll()
 * @method <?= $entity_name ?>[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class <?= $class_name ?>Repository extends EntityRepository implements TranslationRepositoryInterface
{
    use TranslationRepositoryTrait;
}
<?php } ?>
