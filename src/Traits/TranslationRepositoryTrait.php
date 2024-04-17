<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Traits;

use Doctrine\ORM\QueryBuilder;

trait TranslationRepositoryTrait
{
    public function createListQueryBuilder(string $locale): QueryBuilder
    {
        return $this->createQueryBuilder('entity')
            ->addSelect('translation')
            ->leftJoin('entity.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->setParameter('locale', $locale)
        ;
    }

    /**
     * @param string $phrase user input in a ResourceAutocompleteChoiceField
     * @param string $locale computed locale based on default one to output translated results
     * @param int $limit max nb on results in dropdown list
     * @param string $fieldName name of translation entity's field (default "name")
     * @return array
     */
    public function findByPhrase(string $phrase, string $locale, int $limit = 10, string $fieldName = 'name'): array
    {
        return $this->createListQueryBuilder($locale)
            ->where('translation.'.$fieldName.' LIKE :phrase')
            ->setParameter('phrase', '%' . $phrase . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
