<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Services;

use Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Moves a resource to a new position and shifts by one the rows between its old and its new position, and only
 * those: moving up pushes them down (+1), moving down pulls them up (-1). A resource without position is inserted,
 * every row from the new position on is pushed down.
 */
final class PositionShifter
{
    /**
     * Numbers the rows 0 to n-1 in the order of the list (position, rows without position first like in an ascending
     * SQL sort, then id): positions shared by several rows, or missing, become distinct.
     *
     * @param class-string<PositionSortableInterface> $class
     */
    public function renumber(EntityManagerInterface $entityManager, string $class): void
    {
        $metadata = $entityManager->getClassMetadata($class);
        /** @var list<PositionSortableInterface> $resources */
        $resources = $entityManager->createQueryBuilder()
            ->select('resource')
            ->from($metadata->getName(), 'resource')
            ->orderBy('resource.position', 'ASC')
            ->addOrderBy(sprintf('resource.%s', $metadata->getSingleIdentifierFieldName()), 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($resources as $position => $resource) {
            $resource->setPosition($position);
        }
        $entityManager->flush();
    }

    public function move(EntityManagerInterface $entityManager, PositionSortableInterface $resource, int $position): void
    {
        $oldPosition = $resource->getPosition();
        if ($oldPosition === $position) {
            return;
        }

        $entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($resource, $oldPosition, $position): void {
            $metadata = $entityManager->getClassMetadata($resource::class);
            $identifier = $metadata->getSingleIdentifierFieldName();

            $query = $entityManager->createQueryBuilder()
                ->update($metadata->getName(), 'resource')
                ->andWhere(sprintf('resource.%s != :id', $identifier))
                ->setParameter('id', $resource->getId())
            ;

            if (null === $oldPosition || $oldPosition > $position) {
                $query
                    ->set('resource.position', 'resource.position + 1')
                    ->andWhere('resource.position >= :position')
                    ->setParameter('position', $position)
                ;
                if (null !== $oldPosition) {
                    $query->andWhere('resource.position < :oldPosition')->setParameter('oldPosition', $oldPosition);
                }
            } else {
                $query
                    ->set('resource.position', 'resource.position - 1')
                    ->andWhere('resource.position <= :position')
                    ->andWhere('resource.position > :oldPosition')
                    ->setParameter('position', $position)
                    ->setParameter('oldPosition', $oldPosition)
                ;
            }

            $query->getQuery()->execute();

            $resource->setPosition($position);
            $entityManager->flush();
        });
    }
}
