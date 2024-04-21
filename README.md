# Sylius Easy Crud Bundle

Like us, you're a fan of EasyAdmin and Sylius, use this plugin to quickly create custom administration interfaces in Sylius, the same way you can create them in EasyAdmin.

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
