# Sylius Easy Crud Bundle

Allow to add custom CRUD with custom resource and provide some custom fields for Sylius.

## Installation

Install with composer

```bash
composer require agence-adeliom/sylius-easy-crud-plugin
```

## Documentation

### ResourceChoiceField

Is a field that allow you to choose a single resource in a select field

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\AssociationField;

// You have to add this form theme @SyliusEasyCrudPlugin/form/association_widget.html.twig
...
yield ResourceChoiceField::new('page')
    ->setResource('happy_cms.page')
;
```

### ResourceAutocompleteChoiceField

Is a field that allow you to choose one or more resources using an dynamic select field

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ResourceAutocompleteChoiceField;


yield ResourceAutocompleteChoiceField::new('products')
    ->setMultiple()
    ->setLabel('sylius.ui.products')
    ->setChoiceValue('id')
    ->setChoiceName('name')
    ->setResource('sylius.product')
    ->setRepositoryMethod('findByPhrase')
    ->setRemoteCriteriaName('phrase')
    ->setRepositoryArguments([
         'phrase' => '$phrase',
         'locale' => "expr:service('sylius.context.locale').getLocaleCode()",
         'limit' => 10
     ]);
```

### EnumField

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
...
yield EnumField::new('property', "label")
    ->setEnum(YourEnumClass::class);
```

### FormTypeField

This field is a custom integration that allow you to bind any raw form type to your admin.

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\FormTypeField;
...
yield FormTypeField::new('property', "label", YourFormTypeClass::class)
```

### TranslationField

An A2lix TranslationFormBundle integration for EasyAdmin.

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TranslationField;

yield TranslationField::new('translations')
    ->restrictToLocales(['fr_FR'])
    ->addField(
        Field::new('name')
            ->setDisabled(false)
            ->setRequired(true)
            ->setFormTypeOption('constraints', [
                // ...
            ])
    )
    ->addField(
        SlugField::new('slug')
            ->setRequired(true)
    )
    ->hideOnIndex();
```

### ChoiceMaskField

An fork of Sonata's ChoiceMaskField for EasyAdmin.

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ChoiceMaskField;

// You have to add this form theme @SyliusEasyCrudPlugin/form/choice_mask_widget.html.twig
...
yield ChoiceMaskField::new('property', "label")
    ->setChoices([
        'uri' => 'uri',
        'route' => 'route',
    ])
    // Associative array. Describes the fields that are displayed for each choice.
    ->setMap([
        'route' => ['route', 'parameters'],
        'uri' => ['uri'],
    ]);
```

### SortableCollectionField

Is an extension of EasyAdmin's CollectionField that allow you to sort entries.

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SortableCollectionField;

// You have to add this form theme @SyliusEasyCrudPlugin/form/sortable_widget.html.twig
...
// NOTE : property can be a *ToMany or an array.
yield SortableCollectionField::new('property', "label")
    ->setEntryType(YourEntryFromType::class)
    ->allowAdd() // Allow to add new entry
    ->allowDelete() // Allow to remove entries
    ->allowDrag()  // Allow to drag entries
    ;
```

### IconField

Is an icon picker.

#### Usage

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\IconField;

// You have to add this form theme @SyliusEasyCrudPlugin/form/icon_widget.html.twig
...
yield IconField::new('property', "label")
    ->setJsonUrl($url) // Must be a public json file with an array of your icon's classes
    ->setFonts($fonts) // Must be an array of yours fonticon css file
    ->setSelectButtonLabel() // Change label
    ->setCancelButtonLabel()  // Change label
    ->setShowAllButtonLabel()  // Change label
    ->setSearchPlaceholder()  // Change label
    ->setNotResultMessage()  // Change label
    ->setDeleteLabel()
    ;
```

### OembedField

#### Usage
```php
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\OembedField;

// You have to add this form theme @SyliusEasyCrudPlugin/form/widget.html.twig
...
yield OembedField::new('property', "label");
```

##### Twig render

```php
# Get HTML code
{{ property|oembed_html }}

# Get Dimensions
{{ property|oembed_size }}
```

## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)
- [@JeromeEngelnAdeliom](https://github.com/JeromeEngelnAdeliom)

  
