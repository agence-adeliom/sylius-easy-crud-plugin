<?php declare(strict_types=1);
if (isset($class_name)) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $entity_namespace ?? 'App\Entity' ?>;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\AbstractTranslation;
use Sylius\Resource\Model\ResourceInterface;

#[ORM\Entity]
#[ORM\Table(name: '<?= str_replace('translation', '_translation', strtolower($class_name)) ?>')]
class <?= $class_name ?> extends AbstractTranslation implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
<?php } ?>
