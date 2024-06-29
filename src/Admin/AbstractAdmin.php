<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Doctrine\ORM\Mapping\Entity;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\FilterInterface;

abstract class AbstractAdmin extends AbstractFormType implements AdminInterface
{
    public function getResourceClass(): string
    {
        return static::getEntityFqcn();
    }

    abstract public static function getEntityFqcn(): string;

    abstract public static function getDefaultSortColumn(): string;

    /**
     * @return array<string, mixed>
     */
    public static function getRepositoryMethod(): array
    {
        return [
            'method' => 'createListQueryBuilder',
            'arguments' => [
                "expr:service('sylius.context.locale').getLocaleCode()",
            ],
        ];
    }

    public static function getDefaultSortOrder(): string
    {
        return 'asc';
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = Actions::new();
        $actions->setRoutePrefix($this->getName());
        $actions->setRequestConfiguration($this->crudAdminFactory->getRequestConfiguration());
        $actions->setMetadata($this->crudAdminFactory->getMetadata());
        $actions
            ->addBatchAction(Action::BATCH_DELETE)
            ->addItemAction(Crud::PAGE_INDEX, Action::NEW)
            ->addItemAction(Crud::PAGE_INDEX, Action::EDIT)
            ->addItemAction(Crud::PAGE_INDEX, Action::DELETE)
            ->addItemAction(Crud::PAGE_INDEX, Action::DETAIL)

            ->addItemAction(Crud::PAGE_EDIT, Action::DETAIL)
            ->addGlobalAction(Crud::PAGE_EDIT, Action::INDEX)
            ->addGlobalAction(Crud::PAGE_EDIT, Action::DELETE)

            ->addItemAction(Crud::PAGE_DETAIL, Action::EDIT)
            ->addGlobalAction(Crud::PAGE_DETAIL, Action::INDEX)

        ;

        return $actions;
    }

    /**
     * @return iterable<FilterInterface>
     */
    public function configureFilters(): iterable
    {
        //yield BooleanFilter::create('enabled');
        return [];
    }

    /**
     * configure sylius grid addOrderBy(string $name, string $direction = 'asc')
     * @return array<string, string>
     */
    public function configureDefaultSort(): array
    {
        return [];
    }

    /**
     * @throws \ReflectionException
     */
    public function configureRepository(): string
    {
        if (class_exists(self::getResourceClass())) {
            $reflectionClass = new \ReflectionClass(self::getResourceClass());
            foreach ($reflectionClass->getAttributes() as $attribute) {
                if ($attribute->getName() === Entity::class) {
                    if ($attribute->getArguments()['repositoryClass'] ?? $attribute->getArguments()[0] ?? null) {
                        return $attribute->getArguments()['repositoryClass'] ?? $attribute->getArguments()[0];
                    }
                }
            }
        }

        throw new \LogicException(sprintf('No %s attribute found on %s', Entity::class, self::getResourceClass()));
    }

    /**
     * Quand on affiche le formulaire on passe l'id du menu|shared_block en paramètre de l'URL
     * et on affiche le champ menu car on a son id. Cependant lors de la soumission,
     * on n'a pas l'id du menu|shared_block dans l'URL mais dans le corps du formulaire, il faut
     * donc le récupérer dans $_POST dans ce cas-là.
     *
     * @param string $formName Le nom de l'admin type crée par le grid builder
     * @param string $queryKey La clé passée en paramètre l'URL qui contient l'id du menu|shared_block
     * @param string $fieldName Le nom du champ du menu|shared_block dans le formulaire
     *
     * @TODO find a way to get the $formName directly from the current class without having to manually define it
     */
    protected function getResourceFieldValueInRequest(string $formName, string $fieldName, string $queryKey = 'id'): ?string
    {
        if ($this->locator->has('request_stack')) {
            $request = $this->locator->get('request_stack')?->getMainRequest();

            if ($request) {
                $resourceValue = $request->query->get($queryKey) ?? $request->request->get(sprintf('%s[%s]', $formName, $fieldName));
                if ($resourceValue) {
                    return $resourceValue;
                }
                $form = $request->request->all($formName);
                if (isset($form[$fieldName]) && null !== $form[$fieldName]) {
                    return $form[$fieldName];
                }
            }
        }

        return null;
    }
}
