<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Controller;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use FOS\RestBundle\View\View;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Component\Resource\Metadata\Metadata;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class SyliusCrudResourceController extends ResourceController
{
    public function indexAction(Request $request): Response
    {
        /**
         * Specific :
         * 1. Intercept context vars to execute specific actions
         */
        if ($request->query->has('context')) {
            $response = $this->processCustomAction(
                (string) $request->query->get('context'),
                $request,
            );
            if ($response instanceof Response) {
                return $response;
            }
        }

        return parent::indexAction($request);
    }

    private function processCustomAction(string $context, Request $request): ?Response
    {
        if (str_starts_with($context, 'ca:')) {
            $controller = $this->metadata->getParameters()['classes']['controller'];
            $method = str_replace('ca:', '', $context) . 'Action';
            $configuration = $this->requestConfigurationFactory
                ->create($this->metadata, $request);

            $this->isGrantedOr403($configuration, $method);
            $this->isGrantedOr403($configuration, ResourceActions::INDEX);

            $resources = $this->resourcesCollectionProvider->get($configuration, $this->repository);

            if (!(method_exists($this, $method))) {
                throw new HttpException('403', 'Method ' . $method . ' not exists in file ' . $controller);
            }

            return $this->$method(
                $configuration,
                $resources,
                $request,
            );
        }

        return null;
    }

    public function showAction(Request $request): Response
    {
        /**
         * Specific :
         * 1. get form factory and form type
         * 2. call $formType->buildDetail($resource) method and merge parameters
         */
        $configuration = $this->requestConfigurationFactory
            ->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);
        $resource = $this->findOr404($configuration);

        $event = $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $resource);
        $eventResponse = $event->getResponse();
        if (null !== $eventResponse) {
            return $eventResponse;
        }

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory
            ->create($this->metadata->getParameters()['classes']['form']);
        $formType = $form->getConfig()->getType()->getInnerType();
        $formType->resetBuild();

        if (!($formType instanceof AbstractAdmin)) {
            throw new NotFoundResourceException();
        }

        if ($configuration->isHtmlRequest()) {
            return $this->render(
                $configuration->getTemplate(ResourceActions::SHOW . '.html'),
                array_merge(
                    $formType->buildDetail($resource),
                    [
                        'configuration' => $configuration,
                        'metadata' => $this->metadata,
                        'resource' => $resource,
                        'form' => $form->createView(),
                        $this->metadata->getName() => $resource,
                    ],
                ),
            );
        }

        return $this->createRestView($configuration, $resource);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function autocompleteAction(Request $request): Response
    {
        // set metadata for resource that need an autocomplete search
        $resourceName = $request->query->get('resourceName');
        $repositoryMethod = $request->query->get('repositoryMethod');
        $repositoryArguments = json_decode(
            $request->query->get('repositoryArguments'),
            true,
        );

        try {
            /** @var array $resources */
            $resources = $this->container->getParameter('sylius.resources');
        } catch (InvalidArgumentException $exception) {
            throw new HttpException('403', $exception);
        }

        foreach ($resources as $alias => $configuration) {
            if ($resourceName === $alias) {
                $this->metadata = Metadata::fromAliasAndConfiguration($alias, $configuration);
                $syliusRepositoryService = str_replace('sylius.', 'sylius.repository.', $alias);
                if ($this->container->has($syliusRepositoryService)) {
                    $this->repository = $this->container->get($syliusRepositoryService);
                } else {
                    $this->repository = $this->container->get('doctrine')
                        ->getRepository($configuration['classes']['model']);
                }
            }
        }

        $request->attributes->set('_format', 'json');
        $request->attributes->set('_sylius', [
            'serialization_groups' => [
                'Autocomplete',
            ],
            'permission' => true,
            'repository' => [
                'method' => $repositoryMethod,
                'arguments' => $repositoryArguments,
            ],
        ]);

        return $this->indexAction($request);
    }

    public function exportGridResourcesTestGroupAction(
        RequestConfiguration $configuration,
        ResourceGridView $resources,
        Request $request,
    ): Response {
        $resources->getData()->setMaxPerPage(9999);
        $exportableDatas = [];
        foreach ($resources->getData() as $data) {
            $exportableDatas[] = $data;
        }
        $request->setRequestFormat($request->get('format'));

        return $this->createRestView($configuration, $exportableDatas, null, $request->get('groups'));
    }

    protected function createRestView(RequestConfiguration $configuration, $data, int $statusCode = null, ?array $groups = []): Response
    {
        if (empty($groups)) {
            return parent::createRestView($configuration, $data, $statusCode);
        }

        if (null === $this->viewHandler) {
            throw new \LogicException('You can not use the "non-html" request if FriendsOfSymfony Rest Bundle is not available. Try running "composer require friendsofsymfony/rest-bundle".');
        }

        $view = View::create($data, $statusCode);
        $context = $view->getContext()->setGroups($groups);
        $view->setContext($context);

        return $this->viewHandler->handle($configuration, $view);
    }

    private function getAdminClass(): AbstractAdmin
    {
        $formFactory = $this->container->get('form.factory');
        $form = $formFactory
            ->create($this->metadata->getParameters()['classes']['form']);
        $formType = $form->getConfig()->getType()->getInnerType();

        if (!($formType instanceof AbstractAdmin)) {
            throw new NotFoundResourceException();
        }

        return $formType;
    }
}
