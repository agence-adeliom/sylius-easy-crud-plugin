## Creating a custom CRUD

#### 1. Change Sylius default `doctrine.yaml` mapping type to `attribute`
```yaml
mappings:
    App:
        type: attribute
```

#### 2. Create a new Symfony entity

- You can use `php bin/console make:entity`
And follow Sylius documentation to declare entity as a   [Sylius resource](https://docs.sylius.com/en/latest/cookbook/entities/custom-model.html#register-your-entity-as-a-sylius-resource).
- Or, we provide the bundle command `php bin/console make:easy-crud:create-entity Post`

This command will create 3 files :
```bash
$ src/Entity/Post.php
$ src/Entity/PostTranslation.php
$ src/Repository/PostRepository.php
```

#### 3. Generate an CRUD based on your entity

- Execute : `php bin/console make:easy-crud:create-crud Post`

This command will create or modify files :
```bash
# This file allow you to configure your crud
$ creation : src/Admin/PostAdmin.php
# This optional file allow you to create custom action
$ creation : src/Controller/PostController.php
# A Sylius route based on your entity is added automatically
$ modification : config/routes.yaml
# A Sylius resource based on your entity is added automatically
$ modification : config/packages/sylius_resources.yaml
# A Sylius resource based on your entity is added automatically
$ creation and configuration of a Sylius menu Listener : src/Menu/MenuListener.php
```

You can execute without entity name `php bin/console make:easy-crud:create-crud` and choose an existing entity.

- Then, `php bin/console cache:clear`
- Then, `php bin/console doctrine:migrations:diff`
- Then, `php bin/console doctrine:migrations:migrate`

4. Change Sylius Menu Listener to add your new custom crud

Follow documentation [here](https://docs.sylius.com/en/latest/customization/menu.html).

- Do not forget to add new entity into [menu](https://docs.sylius.com/en/latest/customization/menu.html).
- Navigate to /admin/posts/

## Declaring resources with attributes (alternative to YAML)

Instead of the two generated YAML blocks (`config/routes.yaml` + `config/packages/sylius_resource.yaml`),
a resource is declared with **two attributes**: the native `#[\Sylius\Resource\Metadata\AsResource]` on the
**model** (the resource identity / alias), and `#[AsAdmin]` on the **Admin class** (the easy-crud layer that
already holds the fields, grid and form, and points back to the model).

#### 1. Enable discovery (two path lists)

```yaml
# config/packages/sylius_easy_crud.yaml
sylius_easy_crud:
    attributes:
        enabled: true                              # gates the runtime scan AND the maker output
        paths: ['%kernel.project_dir%/src/Admin']  # where the #[AsAdmin] classes live

# config/packages/sylius_resource.yaml
sylius_resource:
    mapping:
        paths: ['%kernel.project_dir%/src/Entity'] # where the #[AsResource] models live
```

#### 2. Declare the model and annotate the Admin

```php
// src/Entity/Post.php
use Sylius\Resource\Metadata\AsResource;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[AsResource(alias: 'app.post')]
class Post implements ResourceInterface { /* ... */ }
```

```php
// src/Admin/PostAdmin.php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;

#[AsAdmin(resourceClass: Post::class, icon: 'newspaper', except: ['show'])]
final class PostAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        yield TabField::new('main', 'Contenu');
        yield Field::new('title');
    }
}
```

`#[AsResource]` carries the resource **identity** (alias). `#[AsAdmin]` carries the admin **data** (section,
icon, routing behaviour…), while the Admin methods keep the **logic**
(`configureFields`/`configureActions`/`configureFilters`). `AbstractAdmin` reads `#[AsAdmin]` as the default
for `getEntityFqcn()` (= `resourceClass`), `getName()` (grid, derived from the class name when omitted —
`PostAdmin` → `admin_post`), `getDefaultSortColumn()`, `getDefaultSortOrder()`, `getLimits()` and
`getRepositoryMethod()` — **override any of these static methods to win over the attribute**. Other arguments:
`alias` (defaults to the model's `#[AsResource]` alias), `section`, `header`, `redirect`, the free-form `vars`
passthrough, `path`…

> **How it's wired:** easy-crud contributes the full resource (controller `SyliusCrudResourceController`,
> form = Admin, repository, translation) in its bundle `prepend()`, keyed by the `#[AsResource]` alias. This
> is required because Sylius materializes the resource's services during the container `load()` phase, before
> any compiler pass could patch them. Sylius' own auto-registration skips an already-declared alias, so
> easy-crud cleanly owns the entry. Route names/URLs are fully determined by `alias` + `section`.

> **Migrating an existing resource:** set `#[AsResource(alias:)]` to the legacy alias (e.g. `'app.log_cron'`)
> to keep route names/URLs byte-identical, then remove the old `routes.yaml` and `sylius_resource.yaml` blocks.
> YAML-declared resources keep working alongside attribute ones, so you can migrate one at a time.

#### 3. Generate with the maker

When `attributes.enabled` is `true`, `php bin/console make:easy-crud:create-crud Post` writes `#[AsResource]`
on the generated entity **and** `#[AsAdmin]` on the generated Admin class instead of the YAML blocks
(everything else is unchanged).

#### Coexistence & deprecation

The two systems coexist — the configuration only switches which one easy-crud uses:

- `attributes.enabled: false` (default): resources keep being declared the legacy way (the generated
  `config/routes.yaml` + `config/packages/sylius_resource.yaml` blocks). **This style is deprecated** and the
  maker emits a deprecation when it generates those blocks.
- `attributes.enabled: true`: the maker and the runtime use `#[AsResource]` + `#[AsAdmin]`. Any resource still
  declared with the legacy YAML blocks keeps working, so you can migrate one resource at a time.

## Next Steps

- **Explore [Field Types](./discover_fields.md)** to understand available fields
- **Review [Entity Traits](./entity_traits.md)** for common entity features
