<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;

if (isset($class_name)) {
    ?>
<?= "<?php\n" ?>

namespace <?= $entity_namespace ?? 'App\Entity' ?>;

use <?= $repository_namespace ?? 'App\Repository' ?>\<?= $class_name ?>Repository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;

#[ORM\Entity(repositoryClass: <?= $class_name ?>Repository::class)]
#[ORM\Table(name: '<?= Str::asSnakeCase(($class_name)) ?>')]
class <?= $class_name ?> implements ResourceInterface, TranslatableInterface
{
    use TranslatableTrait {
        TranslatableTrait::__construct as private initializeTranslationsCollection;
        TranslatableTrait::getTranslation as private doGetTranslation;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
}
<?php } ?>
