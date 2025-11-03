<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Controller;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractFormType;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Resource\ResourceActions;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SyliusCrudResourceController extends ResourceController
{
    use ResourceAutocompleteTrait;

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
                throw new HttpException(Response::HTTP_FORBIDDEN, 'Method ' . $method . ' not exists in file ' . $controller);
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

        /** @var ?FormFactoryInterface $formFactory */
        $formFactory = $this->container->get('form.factory');

        assert($formFactory instanceof FormFactoryInterface, 'form.factory service must be an instance of FormFactoryInterface');

        $form = $formFactory
            ->create(
                $this->metadata->getParameters()['classes']['form'],
                null,
                [
                    'page_name' => Crud::PAGE_DETAIL,
                ],
            );
        $formType = $form->getConfig()->getType()->getInnerType();

        assert($formType instanceof AbstractFormType, 'Form type must be an instance of AbstractFormType');
        assert($formType instanceof AbstractAdmin, 'Form type must be an instance of AbstractAdmin');

        $formType->resetBuild();

        if ($configuration->isHtmlRequest()) {
            $formView = $form->createView();

            /** @var string $template */
            $template = $configuration->getTemplate(ResourceActions::SHOW . '.html');

            return $this->render(
                $template,
                array_merge(
                    $formType->buildDetail($resource),
                    [
                        'configuration' => $configuration,
                        'metadata' => $this->metadata,
                        'resource' => $resource,
                        'form' => $formView,
                        $this->metadata->getName() => $resource,
                    ],
                ),
            );
        }

        return $this->createRestView($configuration, $resource);
    }

    /**
     * @param string[]|null $groups
     */
    protected function createRestView(RequestConfiguration $configuration, $data, int $statusCode = null, ?array $groups = []): Response
    {
        if (empty($groups)) {
            return parent::createRestView($configuration, $data, $statusCode);
        }

        if (null === $this->viewHandler) {
            throw new \LogicException('You can not use the "non-html" request if FriendsOfSymfony Rest Bundle is not available. Try running "composer require friendsofsymfony/rest-bundle".');
        }

        if (!class_exists(View::class)) {
            throw new \LogicException('You can not use the "non-html" request if FriendsOfSymfony Rest Bundle is not available. Try running "composer require friendsofsymfony/rest-bundle".');
        }

        $view = View::create($data, $statusCode);
        $context = $view->getContext()->setGroups($groups);
        $view->setContext($context);

        return $this->viewHandler->handle($configuration, $view);
    }
}
