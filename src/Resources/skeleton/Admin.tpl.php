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
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SortableCollectionField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use <?= $entity ?>;
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
        return 'admin_<?= mb_strtolower(Str::asSnakeCase($entity)) ?>';
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
        ->setSize(ColumnSizeEnum::WIDE_8_OF_12);

        yield Field::new('id')
        ->onlyOnIndex();

    }
}
<?php } ?>
