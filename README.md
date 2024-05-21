# Sylius Easy Crud Bundle

This bundle allow you to quickly create custom admin crud interfaces in Sylius. 
And with a similar way you configure CRUD with EasyAdminBundle.
For example :
```php
public function configureFields(string $pageName, ?string $context = null): iterable
{
    yield TabField::new('my_first_tab', 'Main config');

    yield ColumnField::new('_column1')
        ->setSize(ColumnSizeEnum::WIDE_8_OF_16)
        ->setLabel('Title and enabled fields');

    yield Field::new('title')
        ->setRequired(true)
        ->setFormTypeOption('constraints', [
            new Length(['max' => 60])
        ]);
        
    yield CheckboxField::new('enabled');
    
    yield TabField::new('my_second_tab', 'Second tab');
}
```
This bundle generate an entity Admin class and provide multiple fields to allow you generate 'create', 'edit' and 'show' CRUD view in the same place.
Read more in the documentation section.

## Installation

```bash
composer require agence-adeliom/sylius-easy-crud-plugin
```

Then, into `config/bundles.php` add :

```php
`Adeliom\SyliusEasyCrudPlugin\SyliusEasyCrudPlugin::class => ['all' => true],
```

## Documentation

- Learn how to [generate a custom CRUD](./docs/start_using_easy_crud.md) into Sylius Admin
- [Discover all fields](./docs/discover_fields.md) you can use to build your CRUD (grid, form, show, action, fitters)
- Learn how create your [own fields](./docs/create_your_own_fields.md)
- You want to [help and contribute](./docs/contribution.md)

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Authors

Adeliom
