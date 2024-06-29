<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\CrudAdminFactory;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

abstract class AbstractFormType extends AbstractGridType
{
    private ?object $resource = null;

    public function getResource(): ?object
    {
        return $this->resource;
    }

    private ?UserInterface $user = null;

    public function getUser(): ?object
    {
        return $this->user;
    }

    public function __construct(
        string $dataClass,
        array $validationGroups,
        protected CrudAdminFactory $crudAdminFactory,
        protected LocaleProviderInterface $localeProvider,
        protected EntityManagerInterface $entityManager,
        protected ContainerInterface $locator,
        protected Security $security,
    ) {
        parent::__construct(
            $dataClass,
            $validationGroups,
        );
        $this->user = $security->getUser();
        $this->crudAdminFactory->initContext($this->getResourceClass());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('context', null);
        $resolver->setDefault('page_name', ResourceActions::UPDATE);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $context = $options['context'];
        $pageName = $options['page_name'];
        Assert::string($options['data_class']);

        $this->resource = $options['data'] ?? null;

        $fields = FieldCollection::new(
            $this->configureFields($pageName, $context),
            $this->crudAdminFactory->getFieldConfiguratorCollection(),
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
                    $guessType = $this->crudAdminFactory
                        ->getDoctrineOrmTypeGuesser()
                        ->guessType($options['data_class'], $fieldDto->getProperty());
                    $formFieldType = $guessType->getType();
                    $formFieldOptions = array_merge($guessType->getOptions(), $formFieldOptions);
                }

                if ($fieldDto->getFieldFqcn() === TabField::class) {
                    [$menuItem, $column] = $this->crudAdminFactory
                        ->addTab(
                            $fieldDto->getProperty(),
                            $fieldDto->getLabel(),
                        );
                }

                if ($fieldDto->getFieldFqcn() === ColumnField::class) {
                    $column = $this->crudAdminFactory->addColumn($menuItem, $fieldDto);
                }

                if (
                    $fieldDto->getFieldFqcn() !== TabField::class &&
                    $fieldDto->getFieldFqcn() !== ColumnField::class
                ) {
                    if (
                        $fieldDto->getFieldFqcn() === TranslationField::class
                    ) {
                        $subFieldsDto = FieldCollection::new(
                            $fieldDto->getCustomOption('fieldsDto'),
                            $this->crudAdminFactory->getFieldConfiguratorCollection(),
                        );
                        if (method_exists($options['data_class'], 'getTranslationClass')) {
                            $formFieldOptions['data_translation_class'] = $options['data_class']::getTranslationClass();
                        }
                        $subFields = [];
                        foreach ($subFieldsDto as $subFieldDto) {
                            $formSubFieldOptions = $subFieldDto->getFormTypeOptions();
                            if (null === $formSubFieldType = $subFieldDto->getFormType()) {
                                $guessType = $this->crudAdminFactory
                                    ->getDoctrineOrmTypeGuesser()
                                    ->guessType($options['data_class'], $subFieldDto->getProperty());
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
                            $this->crudAdminFactory->manageFieldAssets($subFieldDto);
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

                    $this->crudAdminFactory->manageFieldAssets($fieldDto);

                    $formField->setAttribute('menuItem', $menuItem);
                    $formField->setAttribute('columnId', $column['id'] ?? null);

                    $builder->add($formField);
                }
            }
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $actions = $this->processDetailAndUpdateActions($form->getConfig()->getOption('page_name'));

        $view->vars = array_merge(
            $view->vars,
            $this->crudAdminFactory->getViewVars(),
            [
                'actionsGroups' => $actions,
            ],
        );
    }

    public function resetBuild(): void
    {
        $this->crudAdminFactory->initMenu();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDetail(ResourceInterface $resource): array
    {
        $fields = FieldCollection::new(
            $this->configureFields(Crud::PAGE_DETAIL),
            $this->crudAdminFactory->getFieldConfiguratorCollection(),
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
                    $propertyAccessor = $this->crudAdminFactory->getPropertyAccessor();
                    $propertyPath = $fieldDto->getProperty();
                    if ($propertyAccessor->isReadable($resource, $propertyPath)) {
                        $value = $propertyAccessor->getValue($resource, $propertyPath);
                        $fieldDto->setValue($value);
                    }
                }

                if ($fieldDto->getFieldFqcn() === TabField::class) {
                    [$menuItem, $column] = $this->crudAdminFactory->addTab(
                        $fieldDto->getProperty(),
                        $fieldDto->getLabel(),
                        '@SyliusEasyCrudPlugin/crud/show/_tab.html.twig',
                    );
                }

                if ($fieldDto->getFieldFqcn() === ColumnField::class && null !== $menuItem) {
                    $column = $this->crudAdminFactory->addColumn($menuItem, $fieldDto);
                }

                if (
                    $fieldDto->getFieldFqcn() !== TabField::class &&
                    $fieldDto->getFieldFqcn() !== ColumnField::class
                ) {
                    $this->crudAdminFactory->manageFieldAssets($fieldDto);
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
            $this->crudAdminFactory->getViewVars(),
            [
                'fields' => $fields,
                'actionsGroups' => $actions,
            ],
        );
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $fields = FieldCollection::new(
            $this->configureFields(Crud::PAGE_INDEX),
            $this->crudAdminFactory->getFieldConfiguratorCollection(),
        );

        foreach ($fields as $fieldDto) {
            /**
             * @var FieldDto $fieldDto
             */
            if (
                $fieldDto->getDisplayedOn()->has(Crud::PAGE_INDEX) &&
                !$fieldDto->isVirtual()
            ) {
                $this->crudAdminFactory->manageFieldAssets($fieldDto);

                if (method_exists($fieldDto->getFieldFqcn(), 'create')) {
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
