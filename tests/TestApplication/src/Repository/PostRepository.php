<?php

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Repository;

use Adeliom\SyliusEasyCrudPlugin\Repository\TranslationRepositoryInterface;
use Adeliom\SyliusEasyCrudPlugin\Traits\TranslationRepositoryTrait;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends EntityRepository implements TranslationRepositoryInterface
{
    use TranslationRepositoryTrait;
}
