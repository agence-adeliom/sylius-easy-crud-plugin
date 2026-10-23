<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Controller;

use Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface;
use Adeliom\SyliusEasyCrudPlugin\Services\PositionShifter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\TreeListener;
use Sylius\Resource\Metadata\RegistryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Drag and drop of the {@see \Adeliom\SyliusEasyCrudPlugin\Admin\Field\PositionSortableField} column: the resource
 * `id` is dropped between `previousId` and `nextId`, the rows around it in the list (only its siblings in a tree).
 * The resource is given by its Sylius alias in the URL and must opt in: implement {@see PositionSortableInterface}
 * (flat list), or be a Gedmo nested tree (tree).
 */
#[AsController]
final readonly class PositionSortController
{
    public const CSRF_TOKEN_ID = 'sylius_easy_crud_position_sort';

    public function __construct(
        #[Autowire(service: 'sylius.resource_registry')]
        private RegistryInterface $resourceRegistry,
        private ManagerRegistry $managerRegistry,
        private PositionShifter $positionShifter,
        private PropertyAccessorInterface $propertyAccessor,
        // null when the CSRF protection of the application is disabled
        private ?CsrfTokenManagerInterface $csrfTokenManager = null,
    ) {
    }

    public function flat(Request $request, string $resource): Response
    {
        [$entityManager, $moved, $previous, $next] = $this->load($request, $resource);
        if (!$moved instanceof PositionSortableInterface) {
            throw new BadRequestHttpException(sprintf('The resource "%s" does not implement %s.', $resource, PositionSortableInterface::class));
        }

        $oldPosition = $moved->getPosition();
        $previousPosition = $previous instanceof PositionSortableInterface ? $previous->getPosition() : null;
        $nextPosition = $next instanceof PositionSortableInterface ? $next->getPosition() : null;

        // positions that cannot express the new order (shared, or missing on a neighbour): renumber the list first
        $neighbourWithoutPosition = (null !== $previous && null === $previousPosition) || (null !== $next && null === $nextPosition);
        $sharedPosition = null !== $oldPosition && \in_array($oldPosition, [$previousPosition, $nextPosition], true);
        $unorderedNeighbours = null !== $previousPosition && null !== $nextPosition && $previousPosition >= $nextPosition;
        if ($neighbourWithoutPosition || $sharedPosition || $unorderedNeighbours) {
            $this->positionShifter->renumber($entityManager, $moved::class);
            $oldPosition = $moved->getPosition();
            $previousPosition = $previous instanceof PositionSortableInterface ? $previous->getPosition() : null;
            $nextPosition = $next instanceof PositionSortableInterface ? $next->getPosition() : null;
        }

        // positions of the neighbours, not indexes in the page: the list may be paginated
        if (null !== $previousPosition && null !== $oldPosition && $previousPosition > $oldPosition) {
            $this->positionShifter->move($entityManager, $moved, $previousPosition);
        } elseif (null !== $nextPosition && (null === $oldPosition || $nextPosition < $oldPosition)) {
            $this->positionShifter->move($entityManager, $moved, $nextPosition);
        } elseif (null === $oldPosition && null !== $previousPosition) {
            $this->positionShifter->move($entityManager, $moved, $previousPosition + 1);
        }

        return new JsonResponse(['position' => $moved->getPosition()]);
    }

    public function tree(Request $request, string $resource): Response
    {
        [$entityManager, $moved, $previous, $next] = $this->load($request, $resource);
        $repository = $entityManager->getRepository($moved::class);
        if (!$repository instanceof NestedTreeRepository) {
            throw new BadRequestHttpException(sprintf('The repository of the resource "%s" is not a Gedmo %s.', $resource, NestedTreeRepository::class));
        }

        $parentProperty = $this->treeParentProperty($entityManager, $moved::class);
        $parent = $this->propertyAccessor->getValue($moved, $parentProperty);
        if (null === $parent) {
            throw new BadRequestHttpException('A root node cannot be moved.');
        }
        foreach ([$previous, $next] as $sibling) {
            if (null !== $sibling && $this->propertyAccessor->getValue($sibling, $parentProperty) !== $parent) {
                throw new BadRequestHttpException('The previous and next nodes must be siblings of the moved node.');
            }
        }

        if (null !== $previous) {
            $repository->persistAsNextSiblingOf($moved, $previous);
        } elseif (null !== $next) {
            $repository->persistAsPrevSiblingOf($moved, $next);
        }
        $entityManager->flush();

        return new JsonResponse([]);
    }

    /**
     * @param class-string $class
     */
    private function treeParentProperty(EntityManagerInterface $entityManager, string $class): string
    {
        foreach ($entityManager->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    return $listener->getConfiguration($entityManager, $class)['parent'] ?? throw new \LogicException(sprintf('%s is not a Gedmo tree.', $class));
                }
            }
        }

        throw new \LogicException('The Gedmo tree listener is not registered.');
    }

    /**
     * @return array{EntityManagerInterface, object, ?object, ?object}
     */
    private function load(Request $request, string $resource): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequestHttpException('The request body must be JSON.');
        }
        if (!is_array($payload) || !isset($payload['id'])) {
            throw new BadRequestHttpException('The request body must give the "id" of the moved resource.');
        }

        if (null !== $this->csrfTokenManager && !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, is_string($payload['_token'] ?? null) ? $payload['_token'] : ''))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        try {
            $modelClass = $this->resourceRegistry->get($resource)->getClass('model');
        } catch (\InvalidArgumentException) {
            throw new NotFoundHttpException(sprintf('Unknown resource "%s".', $resource));
        }

        $entityManager = $this->managerRegistry->getManagerForClass($modelClass);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new NotFoundHttpException(sprintf('The resource "%s" is not managed by the Doctrine ORM.', $resource));
        }

        $find = static function (mixed $id) use ($entityManager, $modelClass): ?object {
            if (!is_int($id) && (!is_string($id) || '' === $id)) {
                return null;
            }

            return $entityManager->find($modelClass, $id) ?? throw new NotFoundHttpException(sprintf('No "%s" resource with id "%s".', $modelClass, $id));
        };

        $moved = $find($payload['id']) ?? throw new BadRequestHttpException('The request body must give the "id" of the moved resource.');

        return [$entityManager, $moved, $find($payload['previousId'] ?? null), $find($payload['nextId'] ?? null)];
    }
}
