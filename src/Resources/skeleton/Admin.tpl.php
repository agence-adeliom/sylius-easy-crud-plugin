<?php declare(strict_types=1);

use Symfony\Bundle\MakerBundle\Str;

if (isset($namespace, $entity, $class_name)) {
    ?>

<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
use Adeliom\SyliusHappyCMSPlugin\Admin\Field\MediaField;
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ColumnField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\IconField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\OembedField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TabField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Action\Action;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\Enum\ColumnSizeEnum;
use Adeliom\SyliusEasyCrudPlugin\Enum\ThreeStateStatusEnum;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class <?= $class_name ?> extends AbstractAdmin implements ServiceSubscriberInterface
{
    public static function getSubscribedServices(): array
    {
        return [
        // MyService::class => MyService::class
        ];
    }

    public static function getName(): string
    {
        return 'app_admin_<?= Str::asSnakeCase(($entity->getShortName())) ?>';
    }

    public static function getEntityFqcn(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }

    public static function getDefaultSortColumn(): string
    {
        return '';
    }

    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('tab1', 'Tab 1');

        /*
        yield Field::new('title')
            ->setDisabled(false)
            ->setRequired(true)
            ->setFormTypeOption('constraints', [
                new Length(['max' => 60])
            ]);

        //if your entity has translations
        //yield Field::new('title')->onlyOnIndex();
        //yield TranslationField::new('translations')
        //    ->addField(
        //         Field::new('title')
        //         ->setDisabled(false)
        //         ->setRequired(true)
        //         ->setFormTypeOption('constraints', [
        //             new Length(['min' => 1])
        //         ]
        //     )
        //)->onlyOnForms();

        yield Field::new('virtual_field')
            ->setLabel('I am virtual')
            ->setVirtual(true);

        yield TabField::new('tab2', 'Tab 2');

        yield Field::new('description')
            ->setDisabled(false)
            ->setFormType(TextareaType::class);

        yield CheckboxField::new('enabled');

        yield IconField::new('icon');

        yield EnumField::new('enum')
            ->setEnum(ThreeStateStatusEnum::class)
            ->renderExpanded(true);
        */
    }
}
<?php } ?>
