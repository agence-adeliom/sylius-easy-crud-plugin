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
| `PositionSortableField` | Drag-and-drop reordering of the list rows |
| `CollectionField` | Collection without drag and drop (same accordion entries) |
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

// The submitted array is re-indexed as a list (stored as a JSON array, never as an object keyed by the form
// names after a deletion). To keep the keys:
yield SortableCollectionField::new('data')
        ->setEntryType(SubType::class)
        ->setFormTypeOption('keep_as_list', false);

// Same collection, same options, without the drag and drop (the order is not edited)
yield CollectionField::new('emails')
        ->setEntryType(EmailType::class)
        ->setEntryTitle(static fn (?string $email): ?string => $email);

```

### Sortable lists

`PositionSortableField` is the position of the resource: a drag and drop handle on the rows of the list, each move
saved at once, and, for a flat list, an optional integer in the forms (`IntegerType`, empty: no position; change it
with `setFormType()`, `setHelp()`… or keep the handle only with `onlyOnIndex()`).

- **While dragging**, a copy of the row follows the pointer and the row itself, emptied and outlined, leaves a gap
  where it will be dropped. The page scrolls when the pointer nears the top or the bottom of the window. **Escape**
  cancels the move: the rows go back to their place, nothing is saved.
- **Keyboard**: once the handle is focused, the arrow keys move the row up or down by one row (one sibling in a tree).
- **Errors**: when a move is refused or fails, the page is reloaded to show the saved order again.

**Flat list**: the resource implements `Adeliom\SyliusEasyCrudPlugin\Model\PositionSortableInterface`, with an
integer Doctrine field named `position`. Sort the list by position; it may be paginated: the new position is the
position of a neighbour of the dropped row (not its index in the page), and only the rows between the old and the new
position are shifted by one. When the positions cannot express the new order (several rows at the same position, a
neighbour without position), the rows of the table are first renumbered from 0, in the order of the list (position,
rows without position first, then id).

```php
public function getDefaultSort(): array
{
    return ['position' => 'asc'];
}

public function configureFields(string $pageName): iterable
{
    // the handle in the list, the integer in the forms
    yield PositionSortableField::new('position', 'Position');
}
```

**Tree**: the resource is a Gedmo nested tree (`#[Gedmo\Tree(type: 'nested')]`, a `NestedTreeRepository`). Sort the
list by the left value and show every sibling on the same page. A row moves among its siblings only (the rows with the
same parent), together with its descendants; root nodes have no handle and cannot be moved. The field is shown in the list only: the order is the tree itself. When the bounds of the tree
are inconsistent (`verify()` fails, e.g. after a manual SQL change), they are rebuilt first (`recoverFast()`, siblings
kept in the order of their left value). Gedmo writes `$node->sibling` while moving a node: add a `sibling` property or
`#[\AllowDynamicProperties]` to the entity.

```php
yield PositionSortableField::new('position', 'Position')->asTree(); // parent property: `parent`
```

**Security**: the moves are posted to the `sylius_easy_crud_admin_position_sort` and
`sylius_easy_crud_admin_position_sort_tree` routes of `config/routes/admin.yaml` (import them behind the admin
firewall), with a CSRF token (`sylius_easy_crud_position_sort`). A move modifies the resource, so the permission of
its update route is checked like the update page does: when the route has `permission` (YAML resource block or
`#[AsAdmin(permission: true)]`), the authorization checker of the Sylius resource controllers must grant
`<application>.<resource>.update`. A resource without update route needs no permission.

**Services**: the positions of a flat list (`Services\PositionShifterInterface`) and the permission check
(`Services\ResourceUpdatePermissionCheckerInterface`) are services, to decorate or replace by their interface.

**Stimulus**: the handle is the `position-sortable` controller of the plugin, registered by Symfony Flex (see
[Installation](./installation.md#step-5-register-the-stimulus-controllers)).

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
yield ColumnField::new('_col1')->setSize(ColumnSizeEnum::WIDE_8_OF_12);
yield ColumnField::new('_col2')->setSize(ColumnSizeEnum::WIDE_4_OF_12);

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
