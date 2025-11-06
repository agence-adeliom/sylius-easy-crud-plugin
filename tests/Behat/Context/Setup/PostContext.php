<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

final class PostContext implements Context
{
    public function __construct(
        private FactoryInterface $postFactory,
        private RepositoryInterface $postRepository,
        private FactoryInterface $taxonFactory,
        private TaxonRepositoryInterface $taxonRepository,
        private FactoryInterface $productFactory,
        private ProductRepositoryInterface $productRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @Given there are :count posts in the database
     */
    public function thereArePostsInTheDatabase(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createPost(sprintf('Post %d', $i));
        }
    }

    /**
     * @Given there is a post named :name
     */
    public function thereIsAPostNamed(string $name): void
    {
        $this->createPost($name);
    }

    /**
     * @Given there are :count enabled posts
     */
    public function thereAreEnabledPosts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createPost(sprintf('Enabled Post %d', $i), true);
        }
    }

    /**
     * @Given there are :count disabled posts
     */
    public function thereAreDisabledPosts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createPost(sprintf('Disabled Post %d', $i), false);
        }
    }

    /**
     * @Given there is a taxon named :name
     */
    public function thereIsATaxonNamed(string $name): void
    {
        $this->createTaxon($name);
    }

    /**
     * @Given there is a product named :name
     */
    public function thereIsAProductNamed(string $name): void
    {
        $this->createProduct($name);
    }

    private function createPost(string $name, bool $enabled = true): Post
    {
        /** @var Post $post */
        $post = $this->postFactory->createNew();
        $post->setName($name);
        $post->setEnabled($enabled);
        $post->setCreatedAt(new \DateTimeImmutable());
        $post->setCurrentLocale('en_US');
        $post->setFallbackLocale('en_US');

        $this->postRepository->add($post);
        $this->entityManager->flush();

        return $post;
    }

    private function createTaxon(string $name): TaxonInterface
    {
        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode(strtolower(str_replace(' ', '_', $name)));
        $taxon->setName($name);
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');

        $this->taxonRepository->add($taxon);
        $this->entityManager->flush();

        return $taxon;
    }

    private function createProduct(string $name): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();
        $product->setCode(strtolower(str_replace(' ', '_', $name)));
        $product->setName($name);
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');

        $this->productRepository->add($product);
        $this->entityManager->flush();

        return $product;
    }
}
