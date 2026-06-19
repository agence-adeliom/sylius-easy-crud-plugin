<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldConfiguratorCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource\ResourceContext;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Resource\ResourceContextResolver;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\View\CrudViewBuilder;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\View\CrudViewBuilderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Resource\ResourceActions;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Webmozart\Assert\Assert;

abstract class AbstractFormType extends AbstractGridType implements ServiceSubscriberInterface
{
    private const CRUD_VIEW_BUILDER_ATTRIBUTE = 'easy_crud_view_builder';

    private ?object $resource = null;

    private ?ResourceContext $resourceContext = null;

    public function getResource(): ?object
    {
        return $this->resource;
    }

    public function getResourceAlias(): ?string
    {
        return $this->resourceContext?->getAlias();
    }

    private ?UserInterface $user = null;

    public function getUser(): ?object
    {
        return $this->user;
    }

    public function getSyliusLocales(): array
    {
        /** @var array<string, mixed> $resources */
        $resources = $this->parameterBag->get('sylius.resources');

        assert(isset($resources['sylius.locale']) && is_array($resources['sylius.locale']), 'Sylius locale resource is not properly configured.');

        /** @var class-string<ResourceInterface> $modelClass */
        $modelClass = $resources['sylius.locale']['classes']['model'];

        if (class_exists($modelClass)) {
            return $this->entityManager->getRepository($modelClass)->findAll();
        }

        return [];
    }

    public function __construct(
        string $dataClass,
        array $validationGroups,
        protected CrudViewBuilderFactory $crudViewBuilderFactory,
        protected ResourceContextResolver $resourceContextResolver,
        protected FieldConfiguratorCollection $fieldConfiguratorCollection,
        protected DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser,
        protected PropertyAccessor $propertyAccessor,
        protected RequestStack $requestStack,
        protected LocaleProviderInterface $localeProvider,
        protected EntityManagerInterface $entityManager,
        protected ContainerInterface $locator,
        protected Security $security,
        public ParameterBagInterface $parameterBag,
    ) {
        parent::__construct(
            $dataClass,
            $validationGroups,
        );
        $this->user = $security->getUser();
        $this->resourceContext = $this->resourceContextResolver->resolve($this->getResourceClass());
    }

    protected function getResourceContext(): ?ResourceContext
    {
        return $this->resourceContext;
    }

    /**
     * Default service-subscriber declaration so every Admin can be autowired the
     * base service locator without repeating the boilerplate. Override to subscribe
     * to additional services.
     *
     * @return array<int|string, string>
     */
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('context', null);
        $resolver->setDefault('page_name', ResourceActions::UPDATE);
    }

    /**
     * @param array{
     *     context: string|null,
     *     data_class: string|null,
     *     data: ?ResourceInterface,
     *     page_name: string
     * } $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $context = $options['context'];
        $pageName = $options['page_name'];
        Assert::string($options['data_class']);

        $this->resource = $options['data'] ?? null;
        $crudViewBuilder = $this->crudViewBuilderFactory->create();
        $builder->setAttribute(self::CRUD_VIEW_BUILDER_ATTRIBUTE, $crudViewBuilder);

        $fields = FieldCollection::new(
            $this->configureFields($pageName, $context),
            $this->fieldConfiguratorCollection,
            $this->resource,
        );

        $menuItem = null;
        $column = 0;

        foreach ($fields as $fieldDto) {
            if (
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_EDIT) ||
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_NEW) ||
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_DETAIL)
            ) {
                $formFieldOptions = $fieldDto->getFormTypeOptions();

                // the names of embedded Doctrine entities contain dots, which are not allowed
                // in HTML element names. In those cases, fix the name but also update the
                // 'property_path' option to keep the original field name
                if (str_contains($fieldDto->getProperty(), '.')) {
                    $formFieldOptions['property_path'] = $fieldDto->getProperty();
                    $name = str_replace(['.', '[', ']'], '_', $fieldDto->getProperty());
                } else {
                    $name = $fieldDto->getProperty();
                }

                if (null === $formFieldType = $fieldDto->getFormType()) {
                    $guessType = $this->doctrineOrmTypeGuesser->guessType($options['data_class'], $fieldDto->getProperty());

                    assert(null !== $guessType, 'Could not guess the form type for the field ' . $fieldDto->getProperty() . '. Make sure the property exists and is mapped in Doctrine.');

                    $formFieldType = $guessType->getType();
                    $formFieldOptions = array_merge($guessType->getOptions(), $formFieldOptions);
                }

                if ($fieldDto->getFieldFqcn() === TabField::class) {
                    $horizontalDisplay = $fieldDto->getCustomOption(TabField::HORIZONTAL_DISPLAY);
                    assert(is_bool($horizontalDisplay) || null === $horizontalDisplay, 'The field option "horizontal_display" should be a boolean.');
                    [$menuItem, $column] = $crudViewBuilder->addTab(
                        name: $fieldDto->getProperty(),
                        label:  $fieldDto->getLabel(),
                        horizontalDisplay:  $horizontalDisplay,
                    );
                }

                if ($fieldDto->getFieldFqcn() === ColumnField::class && $menuItem) {
                    $column = $crudViewBuilder->addColumn($menuItem, $fieldDto);
                }

                if (
                    $fieldDto->getFieldFqcn() !== TabField::class &&
                    $fieldDto->getFieldFqcn() !== ColumnField::class
                ) {
                    if (
                        $fieldDto->getFieldFqcn() === TranslationField::class
                    ) {
                        /** @var iterable<FieldInterface> $fieldsDto */
                        $fieldsDto = $fieldDto->getCustomOption('fieldsDto');
                        $subFieldsDto = FieldCollection::new(
                            $fieldsDto,
                            $this->fieldConfiguratorCollection,
                            $this->resource,
                        );
                        if (method_exists($options['data_class'], 'getTranslationClass')) {
                            $formFieldOptions['data_translation_class'] = $options['data_class']::getTranslationClass();
                        }
                        $subFields = [];
                        foreach ($subFieldsDto as $subFieldDto) {
                            $formSubFieldOptions = $subFieldDto->getFormTypeOptions();
                            if (null === $formSubFieldType = $subFieldDto->getFormType()) {
                                $guessType = $this->doctrineOrmTypeGuesser->guessType($options['data_class'], $subFieldDto->getProperty());

                                assert(null !== $guessType, 'Could not guess the form type for the field ' . $subFieldDto->getProperty() . '. Make sure the property exists and is mapped in Doctrine.');

                                $formSubFieldType = $guessType->getType();
                                $formSubFieldOptions = array_merge($guessType->getOptions(), $formSubFieldOptions);
                            }
                            // the names of embedded Doctrine entities contain dots, which are not allowed
                            // in HTML element names. In those cases, fix the name but also update the
                            // 'property_path' option to keep the original field name
                            if (str_contains($subFieldDto->getProperty(), '.')) {
                                $formFieldOptions['property_path'] = $subFieldDto->getProperty();
                                $subName = str_replace(['.', '[', ']'], '_', $subFieldDto->getProperty());
                            } else {
                                $subName = $subFieldDto->getProperty();
                            }
                            $crudViewBuilder->manageFieldAssets($subFieldDto);
                            $subFields[] = [
                                'type' => $formSubFieldType,
                                'name' => $subName,
                                'options' => $formSubFieldOptions,
                                'customOptions' => $subFieldDto->getCustomOptions()->all(),
                            ];
                        }
                        $formFieldOptions['fields'] = $subFields;

                        if (method_exists($options['data_class'], 'getTranslationClass')) {
                            $formFieldOptions['data_translation_class'] = $options['data_class']::getTranslationClass();
                        }
                    }

                    $formField = $builder
                        ->getFormFactory()
                        ->createNamedBuilder(
                            $name,
                            $formFieldType,
                            null,
                            $formFieldOptions,
                        );

                    $formFieldOptions['field'] = $fieldDto;

                    foreach ($formFieldOptions as $attribute => $value) {
                        if (!$formField->hasAttribute($attribute)) {
                            $formField->setAttribute($attribute, $value);
                        }
                    }

                    $crudViewBuilder->manageFieldAssets($fieldDto);

                    $formField->setAttribute('menuItem', $menuItem);
                    $formField->setAttribute('columnId', $column['id'] ?? null);

                    $builder->add($formField);
                }
            }
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        assert(is_string($form->getConfig()->getOption('page_name')), 'The form option "page_name" should be a string.');
        $crudViewBuilder = $form->getConfig()->getAttribute(self::CRUD_VIEW_BUILDER_ATTRIBUTE);

        if (!$crudViewBuilder instanceof CrudViewBuilder) {
            throw new \LogicException(sprintf('Missing "%s" form build attribute.', self::CRUD_VIEW_BUILDER_ATTRIBUTE));
        }

        $actions = $this->processDetailAndUpdateActions($form->getConfig()->getOption('page_name'));

        $view->vars = array_merge(
            $view->vars,
            $crudViewBuilder->build()->toViewVars(),
            [
                'actionsGroups' => $actions,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDetail(ResourceInterface $resource): array
    {
        $crudViewBuilder = $this->crudViewBuilderFactory->create();
        $fields = FieldCollection::new(
            $this->configureFields(Crud::PAGE_DETAIL),
            $this->fieldConfiguratorCollection,
            $resource,
        );

        $menuItem = null;
        $column = 0;

        foreach ($fields as $key => $fieldDto) {
            /**
             * @var FieldDto $fieldDto
             */
            if (
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_DETAIL)
            ) {
                if ($fieldDto->getProperty()) {
                    $propertyPath = $fieldDto->getProperty();
                    if ($this->propertyAccessor->isReadable($resource, $propertyPath)) {
                        $value = $this->propertyAccessor->getValue($resource, $propertyPath);
                        $fieldDto->setValue($value);
                    }
                }

                if ($fieldDto->getFieldFqcn() === TabField::class) {
                    $horizontalDisplay = $fieldDto->getCustomOption(TabField::HORIZONTAL_DISPLAY);
                    assert(is_bool($horizontalDisplay) || null === $horizontalDisplay, 'The field option "horizontal_display" should be a boolean.');

                    [$menuItem, $column] = $crudViewBuilder->addTab(
                        name: $fieldDto->getProperty(),
                        label:  $fieldDto->getLabel(),
                        template: '@SyliusEasyCrudPlugin/crud/show/_tab.html.twig',
                        horizontalDisplay: $horizontalDisplay,
                    );
                }

                if ($fieldDto->getFieldFqcn() === ColumnField::class && null !== $menuItem) {
                    $column = $crudViewBuilder->addColumn($menuItem, $fieldDto);
                }

                if (
                    $fieldDto->getFieldFqcn() !== TabField::class &&
                    $fieldDto->getFieldFqcn() !== ColumnField::class
                ) {
                    $crudViewBuilder->manageFieldAssets($fieldDto);
                    $fieldDto->setCustomOption('columnId', $column['id'] ?? null);
                    $fieldDto->setCustomOption('menuItem', $menuItem);
                } else {
                    unset($fields[$key]);
                }
            } else {
                unset($fields[$key]);
            }
        }

        $actions = $this->processDetailAndUpdateActions(Crud::PAGE_DETAIL);

        return array_merge(
            $crudViewBuilder->build()->toViewVars(),
            [
                'fields' => $fields,
                'actionsGroups' => $actions,
            ],
        );
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $crudViewBuilder = $this->crudViewBuilderFactory->create();
        $fields = FieldCollection::new(
            $this->configureFields(Crud::PAGE_INDEX),
            $this->fieldConfiguratorCollection,
            null,
        );

        foreach ($fields as $fieldDto) {
            /**
             * @var FieldDto $fieldDto
             */
            if (
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_INDEX) &&
                !$fieldDto->isVirtual()
            ) {
                $crudViewBuilder->manageFieldAssets($fieldDto);

                if ($fieldDto->getFieldFqcn() && method_exists($fieldDto->getFieldFqcn(), 'create')) {
                    $field = $fieldDto->getFieldFqcn()::create($fieldDto->getProperty());

                    $field->setLabel($fieldDto->getLabel());
                    $field->setSortable($fieldDto->isSortable() ?? true, $fieldDto->getSortablePath());

                    // TODO: needed to allow override, but check that there is no conflict
                    $field->setOption('template', $fieldDto->getGridTemplatePath());

                    $field->addOptions([
                       'vars' => ['field' => $fieldDto],
                    ]);

                    $gridBuilder->addField(
                        $field,
                    );
                }
            }
        }

        parent::processGridDefaultSort(
            $gridBuilder,
        );

        parent::processGridActions(
            $gridBuilder,
        );

        parent::processGridFilters(
            $gridBuilder,
        );

        $crudView = $crudViewBuilder->build();
        $gridBuilder->setDriverOption('css_assets', $crudView->getCssAssets());
        $gridBuilder->setDriverOption('js_assets', $crudView->getJsAssets());
        $gridBuilder->setDriverOption('webpack_encore_assets', $crudView->getWebpackEncoreAssets());
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('default', 'default');
    }

    /**
     * @return class-string
     */
    abstract public function configureRepository(): string;

    public function configureActions(string $pageName): Actions
    {
        return Actions::new();
    }
}
