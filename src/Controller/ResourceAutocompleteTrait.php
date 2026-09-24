<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sylius\Component\Resource\Metadata\Metadata;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResourceAutocompleteTrait
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function autocompleteAction(Request $request): Response
    {
        // set metadata for resource that need an autocomplete search
        $resourceName = $request->query->get('resourceName');
        $repositoryMethod = $request->query->get('repositoryMethod');
        $repositoryArguments = [];
        if (is_string($request->query->get('repositoryArguments'))) {
            $repositoryArguments = json_decode(
                $request->query->get('repositoryArguments'),
                true,
            );
        }

        try {
            /** @var array<string, array> $resources */
            $resources = $this->getParameter('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            throw new HttpException(Response::HTTP_FORBIDDEN, $exception->getMessage());
        }

        $container = $this->getRequiredContainer();
        foreach ($resources as $alias => $configuration) {
            if ($resourceName === $alias) {
                $this->metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);
                $syliusRepositoryService = str_replace('sylius.', 'sylius.repository.', $alias);
                if ($container->has($syliusRepositoryService)) {
                    $containerRepository = $container->get($syliusRepositoryService);
                    assert($containerRepository instanceof RepositoryInterface, 'Repository from container must implement RepositoryInterface');
                    $this->repository = $containerRepository;
                } else {
                    $doctrine = $container->get('doctrine');
                    assert($doctrine instanceof ManagerRegistry, 'Doctrine service must implement ManagerRegistry');
                    $modelClass = $this->metadata->getClass('model');
                    assert(class_exists($modelClass), 'Resource model class must exist');
                    $repository = $doctrine->getRepository($modelClass);
                    assert($repository instanceof RepositoryInterface, 'Repository of the resource model must implement RepositoryInterface');
                    $this->repository = $repository;
                }
            }
        }

        $request->attributes->set('_format', 'json');
        $request->attributes->set('_sylius', [
            'serialization_groups' => [
                'Autocomplete',
                'Default',
            ],
            'permission' => true,
            'repository' => [
                'method' => $repositoryMethod,
                'arguments' => $repositoryArguments,
            ],
        ]);

        return $this->indexAction($request);
    }

    private function getRequiredContainer(): ContainerInterface
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new \LogicException('The service container is not available in the controller.');
        }

        return $this->container;
    }
}
