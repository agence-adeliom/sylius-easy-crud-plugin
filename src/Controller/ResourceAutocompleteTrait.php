<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sylius\Component\Resource\Metadata\Metadata;
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

        foreach ($resources as $alias => $configuration) {
            if ($resourceName === $alias) {
                $this->metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);
                $doctrine = $this->container->get(ManagerRegistry::class);
                assert($doctrine instanceof ManagerRegistry);
                /** @phpstan-ignore-next-line */
                $this->repository = $doctrine->getRepository($configuration['classes']['model']);
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
}
