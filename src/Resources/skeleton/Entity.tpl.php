<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;

if (isset($class_name)) {
    ?>
<?= "<?php\n" ?>

namespace <?= $entity_namespace ?? 'App\Entity' ?>;

use <?= $repository_namespace ?? 'App\Repository' ?>\<?= $class_name ?>Repository;
use Doctrine\ORM\Mapping as ORM;
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

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $enabled = null;

    #[ORM\Column(nullable: true)]
    private ?string $state = null;

    #[ORM\Column(nullable: true)]
    private ?string $icon = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    protected function createTranslation(): TranslationInterface
    {
        return new <?= $class_name ?>Translation();
    }

    public static function getTranslationClass(): string
    {
        return <?= $class_name ?>Translation::class;
    }

    public function getTranslation(?string $locale = null): <?= $class_name ?>Translation
    {
        /** @var <?= $class_name ?>Translation $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function setName(?string $name): static
    {
        $this->getTranslation()->setName($name);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->getTranslation()->getDescription();
    }

    public function setDescription(?string $description): static
    {
        $this->getTranslation()->setDescription($description);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }
}
<?php } ?>
