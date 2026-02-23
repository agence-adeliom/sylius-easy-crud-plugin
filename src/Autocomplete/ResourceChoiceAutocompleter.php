<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Autocomplete;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\UX\Autocomplete\Controller\EntityAutocompleteController;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\OptionsAwareEntityAutocompleterInterface;
use Webmozart\Assert\Assert;

final class ResourceChoiceAutocompleter implements OptionsAwareEntityAutocompleterInterface
{
    public const ALIAS = 'adeliom_sylius_easy_crud_resource_choice';

    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private EntitySearchUtil $entitySearchUtil,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function getEntityClass(): string
    {
        $class = $this->options['class'] ?? null;
        Assert::stringNotEmpty($class);

        return $class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        $queryBuilder = $repository->createQueryBuilder('entity');

        $maxResults = $this->options['max_results'] ?? 10;
        if (is_int($maxResults) && $maxResults > 0) {
            $queryBuilder->setMaxResults($maxResults);
        }

        if ('' === trim($query)) {
            return $queryBuilder;
        }

        $searchableFields = $this->extractSearchableFields() ?? $this->guessSearchableFields();

        $this->entitySearchUtil->addSearchClause(
            $queryBuilder,
            $query,
            $this->getEntityClass(),
            $searchableFields,
        );

        return $queryBuilder;
    }

    public function getLabel(object $entity): string
    {
        $choiceLabel = $this->options['choice_label'] ?? null;

        if (is_string($choiceLabel) && '' !== $choiceLabel) {
            $value = $this->propertyAccessor->getValue($entity, $choiceLabel);

            return is_scalar($value) ? (string) $value : '';
        }

        if (is_callable($choiceLabel)) {
            $value = $choiceLabel($entity);

            return is_scalar($value) ? (string) $value : '';
        }

        return (string) $entity;
    }

    public function getValue(object $entity): mixed
    {
        $choiceValue = $this->options['choice_value'] ?? null;

        if (is_string($choiceValue) && '' !== $choiceValue) {
            return $this->propertyAccessor->getValue($entity, $choiceValue);
        }

        if (is_callable($choiceValue)) {
            return $choiceValue($entity);
        }

        $manager = $this->managerRegistry->getManagerForClass($entity::class);
        if (null === $manager) {
            return null;
        }

        $identifierValues = $manager->getClassMetadata($entity::class)->getIdentifierValues($entity);

        return current($identifierValues);
    }

    public function isGranted(Security $security): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void
    {
        $extraOptions = $options[EntityAutocompleteController::EXTRA_OPTIONS] ?? [];

        $this->options = is_array($extraOptions) ? $extraOptions : [];
    }

    /**
     * @return array<int, string>|null
     */
    private function extractSearchableFields(): ?array
    {
        if (!isset($this->options['searchable_fields']) || !is_array($this->options['searchable_fields'])) {
            return null;
        }

        $fields = array_values(
            array_filter(
                $this->options['searchable_fields'],
                fn (mixed $field): bool => is_string($field) && '' !== $field,
            ),
        );

        return [] === $fields ? null : $fields;
    }

    /**
     * @return array<int, string>|null
     */
    private function guessSearchableFields(): ?array
    {
        $choiceName = $this->options['choice_name'] ?? null;
        if (!is_string($choiceName) || '' === $choiceName) {
            return null;
        }

        $entityClass = $this->getEntityClass();
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($entityClass);
        if ($metadata->hasField($choiceName)) {
            return [$choiceName];
        }

        if (!is_a($entityClass, TranslatableInterface::class, true) || !$metadata->hasAssociation('translations')) {
            return null;
        }

        $translationClass = $metadata->getAssociationTargetClass('translations');
        $translationManager = $this->managerRegistry->getManagerForClass($translationClass);
        if (null === $translationManager) {
            return null;
        }

        $translationMetadata = $translationManager->getClassMetadata($translationClass);
        if ($translationMetadata->hasField($choiceName)) {
            return ['translations.' . $choiceName];
        }

        return null;
    }
}
