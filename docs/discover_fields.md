## Discover available fields

Here is the list of default fields available with this plugin and configuration example.

## Table of Contents

- [Available Field Types](#available-field-types)
- [Fields Types](#Field-Types)
  - [Basic Fields](#basic-fields)
  - [Collections Fields](#collections-fields)
  - [CMS Fields](#cms-fields)
  - [Layout Fields](#layout-fields)
  - [Work with translations](#work-with-translations)
- [Configure Fields Visibility](#configure-fields-visibility)



## Available Field Types

The plugin includes 18+ specialized fields:

| Field | Description                          |
|-------|--------------------------------------|
| `Field` | Basic field base on a FormType       |
| `CheckboxField` | Boolean checkbox                     |
| `DateField`, `DateTimeField`, `TimeField` | Date/time pickers                    |
| `ImageField` | File upload with image preview       |
| `TextEditorField` | WYSIWYG rich text editor             |
| `CodeEditorField` | Syntax-highlighted code editor       |
| `SlugField` | Auto-generated URL-friendly slugs    |
| `EnumField` | PHP 8.1+ enum support                |
| `ResourceChoiceField` | Select related Sylius resources      |
| `TranslationField` | Multi-language content editing       |
| `TabField` | Organize fields into tabs            |
| `ColumnField` | Create responsive layouts            |
| `SortableCollectionField` | Drag-and-drop sortable items         |
| `ChoiceMaskField` | Conditional field visibility         |
| `OembedField` | Embed external media (YouTube, etc.) |
| `IconField` | Icon picker                          |
| `FormTypeField` | Use any custom Symfony form type     |

## Field Types

### Basic Fields

```php
// Text input
yield Field::new('title');

// Textarea
yield Field::new('description')->setFormType(TextareaType::class);

// Checkbox
yield CheckboxField::new('enabled');

// Date/Time
yield DateField::new('publishedDate');
yield DateTimeField::new('publishedAt');
yield TimeField::new('publishTime');

// Auto-generated slug
yield SlugField::new('slug')
    ->setTargetFieldName('title');
```

### Choices Fields

```php

// Choice field based on Enum
yield EnumField::new('state')
    ->setEnum(ThreeStateStatusEnum::class)
    ->renderExpanded();

// Select related resource
yield ResourceChoiceField::new('taxon')
    ->setResource('sylius.taxon');

yield ResourceChoiceField::new('products')
        ->setLabel('Products')
        ->hideOnIndex()
        ->setEntityClass(Product::class)
        ->setResourceAlias('sylius.product')
        ->setMultiple();

```

### Collections Fields

```php

// Collection with sortable entries
yield SortableCollectionField::new('data')
        ->setEntryType(SubType::class);

```

### CMS Fields

```php

// Image upload
yield ImageField::new('featuredImage');

// WYSIWYG editor
yield TextEditorField::new('content');

// Code editor with syntax highlighting
yield CodeEditorField::new('customCss')->setLanguage('css');
yield CodeEditorField::new('codeEditor')
    ->setLanguage('json');

// Icon picker
yield IconField::new('icon')
        ->setJsonUrl('/path/to/icons.json')
        ->setFonts(['/path/to/icons.css']);

// Oembed (YouTube, Vimeo, etc.)
yield OembedField::new('videoUrl');

```

### Layout Fields

```php
// Create tabs
yield TabField::new('main', 'Main Information');
yield TabField::new('seo', 'SEO Settings');

// Create columns
yield ColumnField::new('_col1')->setSize(ColumnSizeEnum::WIDE_8_OF_16);
yield ColumnField::new('_col2')->setSize(ColumnSizeEnum::REGULAR_4_OF_16);

// Conditional fields based on choice
yield ChoiceMaskField::new('choice_mask')
            ->renderExpanded()
            ->setVirtual()
            ->setChoices([
                'field1 + virtual2' => 'both',
                'field1' => 'field1',
                'field2' => 'field2',
            ])
            ->setMap([
                'both' => ['field1', 'virtual2'],
                'field1' => ['field1'],
                'field2' => ['field2'],
            ]);

yield Field::new('field1');
yield Field::new('field2');
```

### Work with translations

You can wrap any fields inside a `TranslationField` to manage multi-language content.

```php
// Multi-language content
yield TranslationField::new('translations')
    ->addField(Field::new('name'))
    ->addField(SlugField::new('slug')->setRequired(true));
```

## Configure Fields Visibility

A field can be shown or hidden on specific pages: Index (grid), Form (create/edit), Detail (view).

```php

yield Field::new('propertyName')
    ->hideOnIndex() // Hide on index page (grid)
    ->hideOnForm()  // Hide on create/edit form
    ->hideOnDetail(); // Hide on detail view
    
    ->onlyOnIndex(); // Show only on index page (grid)
    ->onlyOnForms()  // Show only on create/edit form
    ->onlyOnDetail() // Show only on detail view
    
    ->onlyWhenCreating(); // Show only on create form
    ->onlyWhenUpdating()  // Show only on edit form
```
