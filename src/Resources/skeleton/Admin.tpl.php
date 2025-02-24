<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;

if (
    isset($entity, $entityShortName, $namespace)
) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

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
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use <?= $entity ?>;
use App\Entity\Product\Product;
use App\Entity\Taxonomy\Taxon;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class <?= $entityShortName ?>Admin extends AbstractAdmin implements ServiceSubscriberInterface,
AdminInterface
{

    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'admin_app_entity_<?= Str::asSnakeCase(($entityShortName)) ?>';
    }

    public static function getEntityFqcn(): string
    {
        return <?= Str::asClassName($entityShortName) ?>::class;
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
        yield TabField::new('tab1', 'Tab 1');

        yield ColumnField::new('Title')
        ->setSize(ColumnSizeEnum::WIDE_12_OF_16);

        yield TranslationField::new('translations')
        ->addField(
        Field::new('name')
        ->setDisabled(false)
        ->setRequired(true)
        ->setFormTypeOption('constraints', [
        new Length(['min' => 1])
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
            ->setLanguage('json')
            ->setVirtual();

        yield DateField::new('date1')
            ->setVirtual();

        yield DateTimeField::new('date2')
            ->setVirtual();

        yield TimeField::new('time1')
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
        ->hideOnIndex()
        ->setEntityClass(Taxon::class)
        ->setResourceAlias('sylius.taxon');

        yield ResourceChoiceField::new('product')
        ->setVirtual()
        ->setLabel('Product')
        ->hideOnIndex()
        ->setEntityClass(Product::class)
        ->setResourceAlias('sylius.product');

        yield ResourceChoiceField::new('products')
        ->setVirtual()
        ->setLabel('Products')
        ->hideOnIndex()
        ->setEntityClass(Product::class)
        ->setResourceAlias('sylius.product')
        ->setMultiple();

        yield ResourceChoiceField::new('demo')
        ->setVirtual()
        ->setLabel('Demo')
        ->hideOnIndex()
        ->setEntityClass(Demo::class)
        ->setResourceAlias('app.app_entity_demo');

<!--        yield TabField::new('tabcol', 'Collections');-->
<!---->
<!--        yield SortableCollectionField::new('options')-->
<!--            ->setVirtual()-->
<!--            ->setEntryType(ProductCodeChoiceType::class)-->
<!--            ->setLabel('Options')-->
<!--            ->hideOnIndex();-->
<!---->
<!--        yield SortableCollectionField::new('data')-->
<!--            ->setVirtual()-->
<!--            ->setEntryType(DataTestType::class)-->
<!--            ->hideOnIndex();-->

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
<?php } ?>
