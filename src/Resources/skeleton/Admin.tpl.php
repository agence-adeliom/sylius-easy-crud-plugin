<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

if (
    isset($entity, $classNameDetail) &&
     $classNameDetail instanceof ClassNameDetails
) {
    ?>
<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\Admin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\IconField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use <?= $entity ?>;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class <?= $classNameDetail->getShortName() ?>Admin extends AbstractAdmin implements ServiceSubscriberInterface,
AdminInterface
{

    public static function getSubscribedServices(): array
    {
        return [];
    }

    public static function getName(): string
    {
        return 'admin_<?= Str::asSnakeCase(($classNameDetail->getShortName())) ?>';
    }

    public static function getEntityFqcn(): string
    {
        return <?= Str::asClassName($classNameDetail->getShortName()) ?>::class;
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
    }
}
<?php } ?>
