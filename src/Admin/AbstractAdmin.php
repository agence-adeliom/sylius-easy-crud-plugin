<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsEasyCrudAdmin;
use Doctrine\ORM\Mapping\Entity;
use Sylius\Bundle\GridBundle\Builder\Filter\FilterInterface;

abstract class AbstractAdmin extends AbstractFormType implements AdminInterface
{
    /** @var array<class-string, AsEasyCrudAdmin|null> */
    private static array $easyCrudAttributeCache = [];

    public function getResourceClass(): string
    {
        return static::getEntityFqcn();
    }

    /**
     * Entity FQCN. Read from #[AsEasyCrudAdmin(entity: ...)] by default;
     * override this method to set it explicitly.
     */
    public static function getEntityFqcn(): string
    {
        $entity = static::easyCrudAttribute()?->entity;
        if (null !== $entity) {
            return $entity;
        }

        throw new \LogicException(sprintf(
            'No entity defined for "%s": set #[AsEasyCrudAdmin(entity: YourEntity::class)] or override getEntityFqcn().',
            static::class,
        ));
    }

    /**
     * Grid name (also used as route prefix). Read from #[AsEasyCrudAdmin(grid: ...)]
     * by default, otherwise derived from the Admin class name ("PostAdmin" => "admin_post").
     */
    public static function getName(): string
    {
        return static::easyCrudAttribute()?->grid ?? self::deriveEasyCrudGridName();
    }

    public static function getDefaultSortColumn(): string
    {
        return static::easyCrudAttribute()?->defaultSort ?? '';
    }

    /**
     * Returns the #[AsEasyCrudAdmin] attribute declared on the concrete Admin, if any.
     */
    protected static function easyCrudAttribute(): ?AsEasyCrudAdmin
    {
        $class = static::class;

        if (!array_key_exists($class, self::$easyCrudAttributeCache)) {
            $attributes = (new \ReflectionClass($class))->getAttributes(AsEasyCrudAdmin::class);
            self::$easyCrudAttributeCache[$class] = [] === $attributes ? null : $attributes[0]->newInstance();
        }

        return self::$easyCrudAttributeCache[$class];
    }

    private static function deriveEasyCrudGridName(): string
    {
        $shortName = (new \ReflectionClass(static::class))->getShortName();
        $shortName = preg_replace('/Admin$/', '', $shortName) ?? $shortName;
        $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName) ?? $shortName;

        return 'admin_' . mb_strtolower($snake);
    }

    /**
     * Grid data repository method. Read from #[AsEasyCrudAdmin(repositoryMethod: ..., repositoryArguments: ...)]
     * by default, otherwise the translatable-friendly "createListQueryBuilder" with the current locale.
     *
     * @return array<string, mixed>
     */
    public static function getRepositoryMethod(): array
    {
        $attribute = static::easyCrudAttribute();

        return [
            'method' => $attribute?->repositoryMethod ?? 'createListQueryBuilder',
            'arguments' => $attribute?->repositoryArguments ?? [
                "expr:service('sylius.context.locale').getLocaleCode()",
            ],
        ];
    }

    /**
     * @return int[]
     */
    public static function getLimits(): array
    {
        return static::easyCrudAttribute()?->limits ?? [10, 25, 50];
    }

    public static function getDefaultSortOrder(): string
    {
        return static::easyCrudAttribute()?->defaultSortOrder ?? 'asc';
    }

    public function configureActions(string $pageName): Actions
    {
        $actions = Actions::new();
        $actions->setRoutePrefix($this->getName());
        $actions->setRequestConfiguration($this->crudAdminFactory->getRequestConfiguration());
        $actions->setMetadata($this->crudAdminFactory->getMetadata());
        $actions
            ->addBatchAction(Action::BATCH_DELETE)

            ->addItemAction(Crud::PAGE_INDEX, Action::EDIT)
            ->addItemAction(Crud::PAGE_INDEX, Action::DELETE)

            ->addGlobalAction(Crud::PAGE_INDEX, Action::NEW)
            ->addGlobalAction(Crud::PAGE_DETAIL, Action::EDIT)
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
     *
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
        $request = $this->crudAdminFactory->requestStack->getMainRequest();

        if ($request) {
            $resourceValue = $request->query->get($queryKey) ?? $request->request->get(sprintf('%s[%s]', $formName, $fieldName));
            if (is_string($resourceValue)) {
                return $resourceValue;
            }
            $form = $request->request->all($formName);
            if (isset($form[$fieldName]) && is_string($form[$fieldName])) {
                return $form[$fieldName];
            }
        }

        return null;
    }
}
