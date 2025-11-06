<?php

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Repository\PostRepository;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post implements ResourceInterface, TranslatableInterface
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $codeEditor = null;

    #[ORM\Column(nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(nullable: true)]
    private ?string $embed = null;

    #[ORM\Column(nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\ManyToOne(targetEntity: TaxonInterface::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'taxon_id', nullable: true, onDelete: 'set null')]
    private ?TaxonInterface $taxon = null;

    /** @var Collection<int, ProductInterface> */
    #[ORM\ManyToMany(targetEntity: ProductInterface::class)]
    private Collection $products;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->products = new ArrayCollection();
    }

    protected function createTranslation(): TranslationInterface
    {
        return new PostTranslation();
    }

    public static function getTranslationClass(): string
    {
        return PostTranslation::class;
    }

    public function getTranslation(?string $locale = null): PostTranslation
    {
        /** @var PostTranslation $translation */
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

    public function setName(?string $name, ?string $locale = null): static
    {
        $this->getTranslation($locale)->setName($name);

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

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function setProducts(Collection $products): void
    {
        $this->products = $products;
    }


    public function addProduct(ProductInterface $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(ProductInterface $product): static
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }

    public function getTaxon(): ?TaxonInterface
    {
        return $this->taxon;
    }

    public function setTaxon(?TaxonInterface $taxon): void
    {
        $this->taxon = $taxon;
    }

    public function getCodeEditor(): ?string
    {
        return $this->codeEditor;
    }

    public function setCodeEditor(?string $codeEditor): void
    {
        $this->codeEditor = $codeEditor;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }

    public function getEmbed(): ?string
    {
        return $this->embed;
    }

    public function setEmbed(?string $embed): void
    {
        $this->embed = $embed;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
}
