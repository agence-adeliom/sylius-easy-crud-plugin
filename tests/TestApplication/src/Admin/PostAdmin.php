<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ChoiceMaskField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CodeEditorField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateTimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\IconField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ImageField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\OembedField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceChoiceField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SortableCollectionField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Adeliom\SyliusEasyCrudPlugin\Form\Test\DataTestType;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\Taxon;
use Symfony\Component\Validator\Constraints\Length;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

#[AsAdmin(
    resourceClass: Post::class,
    alias: 'tests_adeliom_sylius_easy_crud_plugin.tests_adeliom_sylius_easy_crud_plugin_entity_post',
    grid: 'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post',
)]
final class PostAdmin extends AbstractAdmin
{
    public function configureFilters(): iterable
    {
        yield BooleanFilter::create('enabled')
            ->setLabel('Enabled');
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield Field::new('id')
            ->onlyOnIndex();

        yield Field::new('name')
            ->onlyOnIndex();

        yield TabField::new('tab1', 'Tab 1');

        yield ColumnField::new('tab1_left')
        ->setSize(ColumnSizeEnum::WIDE_8_OF_12);

        yield TranslationField::new('translations')
            ->addField(
                Field::new('name')
                ->setDisabled(false)
                ->setRequired(true)
            )
            ->hideOnIndex();

        yield ColumnField::new('tab1_right')
        ->setSize(ColumnSizeEnum::WIDE_4_OF_12);

        yield IconField::new('icon')
            ->onlyOnForms();

        yield CheckboxField::new('enabled');

        yield TabField::new('tab2', 'Tab 2');

        yield ColumnField::new('tab2_left')
            ->setSize(ColumnSizeEnum::WIDE_6_OF_12);

        yield EnumField::new('state')
            ->setEnum(ThreeStateStatusEnum::class)
            ->renderExpanded();

        yield CodeEditorField::new('codeEditor')
            ->setLanguage('json');

        yield ColumnField::new('tab2_right')
            ->setSize(ColumnSizeEnum::WIDE_6_OF_12);

        yield DateField::new('date1')
            ->setHelp('is virtual')
            ->setVirtual();

        yield DateTimeField::new('date2')
            ->setHelp('is virtual')
            ->setVirtual();

        yield Field::new('virtual1')
            ->setLabel('virtual1')
            ->setVirtual();

        yield TimeField::new('time1')
            ->setHelp('is virtual')
            ->setVirtual();

        yield TabField::new('tab3', 'Tab 3');

        yield ImageField::new('image');

        yield OembedField::new('embed')
            ->hideOnIndex();

        yield TabField::new('tabrel', 'Relations');

        yield ResourceChoiceField::new('taxon')
            ->setLabel('Taxon')
                ->onlyOnForms()
            ->setEntityClass(Taxon::class)
            ->setResourceAlias('sylius.taxon');

        yield ResourceChoiceField::new('products')
            ->setLabel('Products')
            ->hideOnIndex()
            ->setResourceAlias('sylius.product')
            ->setMultiple();

        yield ResourceChoiceField::new('relatedPosts')
            ->setLabel('Related posts')
            ->hideOnIndex()
            ->setResourceAlias('tests_adeliom_sylius_easy_crud_plugin.tests_adeliom_sylius_easy_crud_plugin_entity_post')
            ->setMultiple();

        yield ResourceChoiceField::new('productsAsJson')
            ->setLabel('Products json encoded')
            ->valueIsPersistedIntoAnArray()
            ->hideOnIndex()
            ->setResourceAlias('sylius.product')
            ->setMultiple();

        yield SortableCollectionField::new('data')
            ->setEntryType(DataTestType::class)
            ->hideOnIndex();

        yield TabField::new('choice_mask', 'Choice mask');

        yield ChoiceMaskField::new('choice_mask')
            ->renderExpanded()
            ->setVirtual()
            ->setChoices([
                'virtual1 + virtual2' => 'both',
                'virtual1' => 'virtual1',
                'virtual2' => 'virtual2',
            ])
            ->setMap([
                'both' => ['virtual1', 'virtual2'],
                'virtual1' => ['virtual1'],
                'virtual2' => ['virtual2'],
            ]);

        yield Field::new('virtual2')
        ->setLabel('virtual2')
        ->setVirtual();
    }
}
