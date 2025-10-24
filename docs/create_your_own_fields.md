## Customizing Sylius Easy CRUD Plugin

### Templates path

Override default templates by creating files in `templates/bundles/SyliusEasyCrudPlugin/`:

```
templates/
└── bundles/
    └── SyliusEasyCrudPlugin/
        ├── crud/
        │   ├── index.html.twig
        │   ├── edit.html.twig
        │   └── new.html.twig
        └── field/
            └── custom_field.html.twig
```

### Custom Field Configurators

Create your own field behavior by implementing `FieldConfiguratorInterface`:

```php
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\FieldConfiguratorInterface;

class MyCustomConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return $field->getFieldFqcn() === MyCustomField::class;
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContextInterface $context): void
    {
        // Custom field configuration logic
    }
}
```

Register it as a service with the `sylius.easy_crud.field_configurator` tag.
