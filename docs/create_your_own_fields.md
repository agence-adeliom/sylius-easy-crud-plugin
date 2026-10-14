## Create your own fields

When the [available fields](./discover_fields.md) are not enough, create a field in your project.
A field is made of up to four pieces:

| Piece | Role | Required |
|---|---|---|
| Field class (`FieldInterface` + `FieldTrait`) | What you `yield` in `configureFields()`: form type, form theme, assets, grid/show templates | yes |
| Form type (`AdminFormTypeInterface`) | Options, view variables, block prefix | only for a new widget |
| Form theme (Twig) | Renders the widget | only for a new widget |
| Assets (JS/CSS) | Client-side behaviour | optional |

The `CharCounterField` below is a complete example of the four pieces.

### 1. The form type

Extend a Symfony type with `getParent()`, give it its own block prefix, and implement
`AdminFormTypeInterface` to declare the form themes and assets the widget needs:

```php
namespace App\Form\Admin;

use Adeliom\SyliusEasyCrudPlugin\Form\AdminFormTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CharCounterType extends AbstractType implements AdminFormTypeInterface
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'char_counter'; // the theme block is "char_counter_widget"
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('max_length', null);
        $resolver->setAllowedTypes('max_length', ['null', 'int']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['max_length'] = $options['max_length'];
    }

    public static function configureAdminAssets(): array
    {
        return []; // e.g. ['js' => ['/build/admin/char-counter.js'], 'css' => [...]]
    }

    public static function configureAdminFormThemes(): array
    {
        return ['admin/form/char_counter.html.twig'];
    }
}
```

Form types without constructor arguments need no service definition.

### 2. The form theme

Name the block after the block prefix. Call `form_widget(form, ...)` inside it to render the
parent widget (Symfony falls back to the parent type's block), then add your markup:

```twig
{# templates/admin/form/char_counter.html.twig #}
{% block char_counter_widget -%}
    {{- form_widget(form, {attr: attr|merge({'data-char-counter': id ~ '_counter'})}) -}}
    <small id="{{ id }}_counter" class="form-text">
        <span>{{ (value ?? '')|length }}</span>{% if max_length %} / {{ max_length }}{% endif %}
    </small>
{%- endblock %}
```

If the widget needs inline JavaScript:

- **never print a form value inside a `<script>`**: read it from the field in JavaScript (a quote or a new line
  breaks the script, and `</script>` in a value injects HTML);
- escape any other Twig variable used in JavaScript with `|e('js')`, e.g. `document.getElementById('{{ id|e('js') }}')`;
- prefer a listener on `document` (event delegation): fields added later from a collection prototype are covered.

### 3. The field class

Use `FieldTrait` and wire the form type, its themes and assets in `new()`. Add fluent methods
for the options your users will set:

```php
namespace App\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use App\Form\Admin\CharCounterType;
use Symfony\Contracts\Translation\TranslatableInterface;

final class CharCounterField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, TranslatableInterface|string|false|null $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(CharCounterType::class)
            ->addFormThemes(CharCounterType::configureAdminFormThemes())
            ->addAssets(CharCounterType::configureAdminAssets())
            ->setGridTemplatePath('@SyliusEasyCrudPlugin/field/default/grid.html.twig')
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/default/show.html.twig')
        ;
    }

    public function setMaxLength(?int $maxLength): self
    {
        return $this->setFormTypeOption('max_length', $maxLength);
    }
}
```

Useful `FieldTrait` methods: `setFormType()`, `setFormTypeOption()`, `addFormThemes()`, `addAssets()` /
`addJsFiles()` / `addCssFiles()`, `setGridTemplatePath()`, `setShowTemplatePath()`, `setCustomOption()`,
`setDefaultColumns()`, `setHelp()`, `onlyOnForms()`, `onlyOnIndex()`…

Grid and show templates receive the value as `data` (and the field as `field` on the show page);
copy `@SyliusEasyCrudPlugin/field/default/*.html.twig` to start a custom one.

### 4. Use it

```php
public function configureFields(string $pageName, ?string $context = null): iterable
{
    yield CharCounterField::new('metaTitle')->setMaxLength(60);
}
```