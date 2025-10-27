<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
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
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Core\Model\TaxonInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

final class PostAdmin extends AbstractAdmin implements ServiceSubscriberInterface, AdminInterface
{
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post';
    }

    public static function getEntityFqcn(): string
    {
        return Post::class;
    }

    public static function getDefaultSortColumn(): string
    {
        return '';
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield Field::new('id')
            ->onlyOnIndex();

        yield TabField::new('tab1', 'Tab 1');

        yield ColumnField::new('Title')
        ->setSize(ColumnSizeEnum::WIDE_12_OF_16);

        yield TranslationField::new('translations')
        ->addField(
            Field::new('name')
            ->setDisabled(false)
            ->setRequired(true)
            ->setFormTypeOption('constraints', [
                new Length(['min' => 1]),
            ]
            )
        )->onlyOnForms();

        yield ColumnField::new('data')
        ->setSize(ColumnSizeEnum::WIDE_4_OF_16);

        yield IconField::new('icon');

        yield CheckboxField::new('enabled');

        yield TabField::new('tab2', 'Tab 2');

        yield EnumField::new('state')
            ->setEnum(ThreeStateStatusEnum::class)
            ->renderExpanded();

        yield CodeEditorField::new('codeEditor')
            ->setLanguage('json');

        yield DateField::new('date1')
            ->setHelp('is virtual')
            ->setVirtual();

        yield DateTimeField::new('date2')
            ->setHelp('is virtual')
            ->setVirtual();

        yield TimeField::new('time1')
            ->setHelp('is virtual')
            ->setVirtual();

        yield TabField::new('tab3', 'Tab 3');

        yield ImageField::new('image')
            ->setVirtual();

        yield OembedField::new('embed')
            ->setVirtual();

        yield TabField::new('tabrel', 'Relations');

        yield ResourceChoiceField::new('taxon')
        ->setVirtual()
        ->setLabel('Taxon')
            ->onlyOnForms()
        ->setEntityClass(Taxon::class)
        ->setResourceAlias('sylius.taxon');

        yield ResourceChoiceField::new('products')
        ->setLabel('Products')
        ->hideOnIndex()
        ->setEntityClass(Product::class)
        ->setResourceAlias('sylius.product')
        ->setMultiple();

        yield SortableCollectionField::new('data')
            ->setVirtual()
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

        yield Field::new('virtual1')
        ->setLabel('virtual1')
        ->setVirtual();

        yield Field::new('virtual2')
        ->setLabel('virtual2')
        ->setVirtual();
    }
}
