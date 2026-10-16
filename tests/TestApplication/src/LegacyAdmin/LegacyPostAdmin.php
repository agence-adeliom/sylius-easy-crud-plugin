<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\LegacyAdmin;

use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

/**
 * An Admin written for easy-crud 2.3, without #[AsAdmin]: its resource and its routes are declared in YAML
 * (tests/TestApplication/config/yaml). Only registered in the "test_yaml" environment.
 */
final class LegacyPostAdmin extends AbstractAdmin
{
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

        yield Field::new('name')
            ->setLabel('Legacy post name')
            ->onlyOnIndex();

        yield TranslationField::new('translations')
            ->addField(
                Field::new('name')
                    ->setLabel('Legacy post name')
                    ->setDisabled(false)
                    ->setRequired(true),
            )
            ->hideOnIndex();

        yield CheckboxField::new('enabled')
            ->setLabel('Legacy post enabled');
    }
}
