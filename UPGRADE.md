# Upgrade

## 2.3.3

### New `PositionSortableField`

Drag and drop reordering of the list rows, flat list or Gedmo tree (see
[Discover fields](docs/discover_fields.md#sortable-lists)). Its handle is a Stimulus controller of the plugin: the
package now has the `symfony-ux` keyword, so Symfony Flex adds the plugin assets to `package.json` and the controller
to `assets/controllers.json` on `composer update`; rebuild the admin assets afterwards
([Installation, step 5](docs/installation.md#step-5-register-the-stimulus-controllers)).

A move may change other rows than the moved one: the rows in between are shifted, the whole table is renumbered when
positions are shared or missing, and an inconsistent tree is rebuilt before the move. It requires the permission of
the update route of the resource.

The grid fields of the Admins receive the alias of the resource in their template variables
(`options.vars.resource_alias`), next to `options.vars.field`.

### `PositionSortableActionTrait` removed

`Adeliom\SyliusEasyCrudPlugin\Traits\Admin\PositionSortableActionTrait` is removed: it depended on
`CrudFactory\Context\AdminContext`, a class the plugin no longer has, so it could not be used. Replace its
`sortPositionAction()` by `PositionSortableField::new()->asTree()`, saved by the plugin's own controller.

## 2.3.2

### Backward compatibility breaks

These changes only affect code that extends the plugin classes: an Admin overriding the constructor or using
`$this->crudAdminFactory`, a controller extending or replacing `SyliusCrudResourceController`, or services
referencing the removed ones. Admins that only implement `configure*()` methods are not affected.

#### Conflict with `agence-adeliom/sylius-happy-cms-plugin` < 2.3.1

happy-cms before 2.3.1 uses `CrudAdminFactory` (removed below), so the plugin declares a Composer `conflict` with it.
Update both packages together:

```bash
composer update agence-adeliom/sylius-easy-crud-plugin agence-adeliom/sylius-happy-cms-plugin
```

#### `CrudAdminFactory` removed, replaced by `CrudViewBuilder`

`Adeliom\SyliusEasyCrudPlugin\CrudFactory\CrudAdminFactory` and its services are removed:

- `adeliom.sylius.crud.admin.factory`;
- `Adeliom\SyliusEasyCrudPlugin\CrudFactory\CrudAdminFactory` (alias).

It was a shared service holding per-request state (menu, columns, assets) mutated by every form build. That state
now lives in `CrudFactory\View\CrudViewBuilder`, a new instance per form/grid/detail build created by
`CrudFactory\View\CrudViewBuilderFactory::create()` and turned into a read-only `CrudView` by `build()`. The resource
context (alias, metadata, request configuration) is resolved once by `CrudFactory\Resource\ResourceContextResolver`.
`AbstractFormType` no longer has a `$crudAdminFactory` property; in an Admin class:

| Before | After |
|---|---|
| `$this->crudAdminFactory->requestStack` | `$this->requestStack` |
| `$this->crudAdminFactory->getFieldConfiguratorCollection()` | `$this->fieldConfiguratorCollection` |
| `$this->crudAdminFactory->getDoctrineOrmTypeGuesser()` | `$this->doctrineOrmTypeGuesser` |
| `$this->crudAdminFactory->getPropertyAccessor()` | `$this->propertyAccessor` |
| `$this->crudAdminFactory->getMetadata()` / `getRequestConfiguration()` | `$this->getResourceContext()?->getMetadata()` / `getRequestConfiguration()` |
| `$this->crudAdminFactory->initContext($model)` | `$this->resourceContextResolver->resolve($model)` (already done in the constructor) |
| `$this->crudAdminFactory->addTab()` / `addColumn()` / `manageFieldAssets()` | the same methods on a `$this->crudViewBuilderFactory->create()` builder |
| `$this->crudAdminFactory->getViewVars()`, `->cssAssets`, `->jsAssets`… | `$builder->build()->toViewVars()`, `->getCssAssets()`, `->getJsAssets()`… |

A service that injected `adeliom.sylius.crud.admin.factory` (or `CrudAdminFactory`) must inject the collaborator it
used instead: `CrudViewBuilderFactory`, `ResourceContextResolver`, `FieldConfiguratorCollection`,
`form.type_guesser.doctrine` or `property_accessor`.

#### `AbstractFormType::resetBuild()` removed

`resetBuild()` reset the menu of the shared `CrudAdminFactory` between two builds, and
`SyliusCrudResourceController::updateAction()` called it. Each build now gets its own `CrudViewBuilder`, so there is
nothing to reset and the method is gone: a controller (or any code) calling `$formType->resetBuild()` fails with
*Call to undefined method …::resetBuild()*. Remove the call.

#### New constructor arguments of `AbstractFormType` (and so of `AbstractAdmin` and every Admin)

```php
// Before
public function __construct(
    string $dataClass,
    array $validationGroups,
    CrudAdminFactory $crudAdminFactory,
    LocaleProviderInterface $localeProvider,
    EntityManagerInterface $entityManager,
    ContainerInterface $locator,
    Security $security,
    ParameterBagInterface $parameterBag,
)

// After
public function __construct(
    string $dataClass,
    array $validationGroups,
    CrudViewBuilderFactory $crudViewBuilderFactory,
    ResourceContextResolver $resourceContextResolver,
    FieldConfiguratorCollection $fieldConfiguratorCollection,
    DoctrineOrmTypeGuesser $doctrineOrmTypeGuesser,
    PropertyAccessorInterface $propertyAccessor,
    RequestStack $requestStack,
    LocaleProviderInterface $localeProvider,
    EntityManagerInterface $entityManager,
    ContainerInterface $locator,
    Security $security,
    ParameterBagInterface $parameterBag,
)
```

The Admins are autowired (the plugin adds `$dataClass` and `$validationGroups`), so nothing changes unless an Admin
overrides the constructor: it must then declare the new arguments and pass them to `parent::__construct()` in this
order. `ResourceContext`, `ResourceContextResolver`, `CrudView`, `CrudViewBuilder` and `CrudViewBuilderFactory` are new
classes.

`CrudRenderExtension`, `ResourcePropertyAccessorExtension` and `FieldResourceTranslationsType` now type their property
accessor argument (and property) with `PropertyAccessorInterface` instead of the concrete `PropertyAccessor`.

### Bug fix: no edited `id` in the index links of the update page

The `type: sylius.resource` block generated by `make:easy-crud:create-crud` set `vars.update.route.parameters` to
`context: $context` **and** `id: $id`. Sylius 2 builds the index links of the update page (breadcrumb, back button)
with these parameters, so they pointed to the list with a stray `?id=N` query parameter. The maker no longer generates
`id: $id` there (the `redirect` parameters keep it), and the `#[AsAdmin]` derived vars do the same. Routes generated
before keep working; remove `id: $id` under `vars.update.route.parameters` to get clean links.

### Deprecation: declaring resources via YAML

Declaring an easy-crud resource through the generated YAML blocks — the
`type: sylius.resource` block in `config/routes.yaml` **and** the
`sylius_resource.resources.<alias>` block in `config/packages/sylius_resource.yaml` —
is **deprecated** and will be **removed in 3.0**.

After upgrading, a deprecation is triggered (at container build) for every easy-crud
resource of the application still declared this way. The resources whose model and Admin both live outside the
application — outside the project directory (`kernel.project_dir`, symlinks resolved) or in Composer's `vendor/`
directory — are declared by another plugin (e.g. happy-cms, including when it is installed from a symlinked path
repository mounted elsewhere) and are not reported, as the application cannot migrate them. Example:

> Since agence-adeliom/sylius-easy-crud-plugin 2.3.2: Declaring the easy-crud resource "app.log_cron" through
> legacy YAML (sylius_resource + "type: sylius.resource") is deprecated and will be removed in 3.0. Set
> "sylius_easy_crud.attributes.enabled: true" and declare it with #[AsAdmin] on its Admin class instead: see
> "How to remove the deprecations" in vendor/agence-adeliom/sylius-easy-crud-plugin/UPGRADE.md.

An Admin carrying `#[AsAdmin]` while `sylius_easy_crud.attributes.enabled` is `false` (the default) is reported by
another deprecation, listing its grid name: the attribute then only provides the defaults of the Admin (resource class,
grid name, sort, limits…), its resource and routes are still read from the YAML blocks, and Sylius finds its grid only if
the `grid` of its YAML route equals that name. When the grid name cannot be derived (no `grid:`, no `getName()`, and a
model without `#[AsResource]` nor `alias:`), the compilation fails with a message telling to enable the discovery or to
set `#[AsAdmin(grid:)]`. Enable the discovery in the same change as the attributes.

Both systems keep working side by side, so you can migrate **one resource at a time**. A given resource is
declared by one of them only: the container compilation fails when an alias is declared both with `#[AsAdmin]`
and with a `sylius_resource.resources.<alias>` block, or with `#[AsAdmin]` on two Admin classes.

### The new declaration model

A resource is now declared with **two attributes**:

1. **`#[\Sylius\Resource\Metadata\AsResource]` on the model (entity)** — the native Sylius 2
   attribute that declares the resource identity (its `alias`). This is the single source of
   truth for the model and alias.
2. **`#[Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin]` on the Admin class** — the easy-crud
   layer: it points back to the resource via `resourceClass:` and carries the admin UI/routing
   metadata (section, icon, grid, redirect, except…).

> **Why two attributes / why easy-crud still works in `prepend()`.** Sylius auto-registers every
> `#[AsResource]` it finds, but with the **default** `ResourceController` and `DefaultResourceType`
> form. easy-crud needs its own controller (`SyliusCrudResourceController`), the Admin as form, the
> entity repository and — for translatable models — a translation sub-resource. Those per-resource
> services are **materialized by the Sylius resource driver during the container `load()` phase**,
> i.e. *before* any compiler pass runs. A compiler pass that patched the resource afterwards would
> be too late (the controller would stay `ResourceController`, and the translation sub-resource would
> never be created). So easy-crud contributes the **full** resource configuration in its bundle
> `prepend()` (`prependExtensionConfig('sylius_resource', …)`), keyed by the `#[AsResource]` alias.
> Sylius' own auto-registration **skips any alias already declared**, so easy-crud cleanly owns the
> entry. `#[AsResource]` remains the declarative identity; easy-crud reads its alias by reflection.

### Automated migration (Claude Code skill)

This package ships a Claude Code skill that performs the migration for you:
**`docs/skills/easy-crud-migrate-to-attributes/SKILL.md`**.

Copy that skill directory into your project's `.claude/skills/` (or your user-level
`~/.claude/skills/`), then ask Claude Code to *"migrate easy-crud resources to attributes"*
(or invoke `/easy-crud-migrate-to-attributes`). It finds the Admin classes, adds `#[AsResource]`
on each model, moves each YAML declaration onto the Admin as `#[AsAdmin]`, removes the now-redundant
boilerplate, deletes the legacy YAML blocks, and verifies the routes are unchanged.

The manual steps below are what that skill automates.

### How to remove the deprecations

#### 1. Enable discovery (two path lists)

```yaml
# config/packages/sylius_easy_crud.yaml
sylius_easy_crud:
    attributes:
        enabled: true
        paths: ['%kernel.project_dir%/src/Admin']    # where your #[AsAdmin] classes live

# config/packages/sylius_resource.yaml
sylius_resource:
    mapping:
        paths: ['%kernel.project_dir%/src/Entity']    # where your #[AsResource] models live
```

`attributes.paths` (Admins) and `sylius_resource.mapping.paths` (models) are **separate**: the
first is scanned by easy-crud for `#[AsAdmin]`, the second is the native Sylius path for `#[AsResource]`.

Both options are read while the container is built: `enabled` accepts a boolean or a `%parameter%` holding one, but
not an environment variable (`%env(…)%` fails the compilation, as its value is not known yet), and every directory of
`paths` must exist when `enabled` is `true` (a missing one fails the compilation instead of being skipped).

#### 2. Declare the model as a resource

Add `#[AsResource]` on the entity and **keep the legacy alias** so route names and URLs stay identical. The
route options are not byte-identical to the YAML generated by the maker: the derived `vars` drop the edited `id` from
`vars.update.route.parameters` (see the bug fix below), and the grid name is derived from the alias unless `grid:`
is set; `#[AsAdmin(defaultVars: false, vars: …)]` reproduces a `resource:` block exactly.

```php
use Sylius\Resource\Metadata\AsResource;

#[ORM\Entity(repositoryClass: CronRepository::class)]
#[AsResource(alias: 'app.log_cron')]   // keep the legacy alias to preserve routes/URLs
class Cron implements ResourceInterface
{
    // ...
}
```

#### 3. Move the admin declaration onto the Admin class

Add `#[AsAdmin]` on the Admin, pointing at the model via `resourceClass:`.

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;

#[AsAdmin(
    resourceClass: Cron::class,   // the #[AsResource] class
    alias: 'app.log_cron',        // optional: defaults to the #[AsResource] alias
    section: 'admin',
    icon: 'file image outline',
    except: ['update'],
)]
final class CronAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable
    {
        // ...
    }
}
```

`alias` on `#[AsAdmin]` is optional: when omitted, easy-crud reads it from the model's
`#[AsResource]`. When set, easy-crud registers the resource under this alias itself, so the model does not need
`#[AsResource]`; if the model has one and is scanned by `sylius_resource.mapping.paths`, give it the same alias,
otherwise Sylius also registers the model under its `#[AsResource]` alias, as a second resource.

Mapping from the legacy `resource:` block to attribute arguments:

| Legacy YAML                              | New attribute argument                           |
|------------------------------------------|--------------------------------------------------|
| `alias`                                  | `#[AsResource(alias:)]` (read by `#[AsAdmin]`)   |
| `classes.model`                          | `#[AsAdmin(resourceClass:)]`                     |
| `section`                                | `#[AsAdmin(section:)]` (default `admin`)         |
| `prefix` / `host` (route import)         | the import of the attribute routes (see below); `#[AsAdmin(prefix:)]` adds a per-resource prefix (none by default) |
| `templates`                              | `#[AsAdmin(templates:)]` (default easy-crud)     |
| `grid`                                   | `#[AsAdmin(grid:)]` (default `<application>_<section>_<name>`, e.g. `app_admin_post`) |
| `form.type`                              | the Admin class itself (no argument)             |
| `except` / `only`                        | `#[AsAdmin(except:)]` / `only:`                  |
| `redirect`                               | `#[AsAdmin(redirect:)]` (default `update`)       |
| `permission`                             | `#[AsAdmin(permission:)]` (default: omitted)     |
| `vars.all.icon` / `header` / `subheader` | `icon` / `header` / `subheader` / `breadcrumb`   |
| anything else under `vars`               | `#[AsAdmin(vars:)]` (free-form, deep-merged)     |
| custom controller (`classes.controller`) | `#[AsAdmin(controller:)]`                        |
| `classes.repository`                     | `#[AsAdmin(repository:)]` (default: the model's `repositoryClass` when it implements `Sylius\Resource\Doctrine\Persistence\RepositoryInterface`, otherwise the Sylius default) |
| anything else of the resource entry (`classes.factory`, `translation.classes.*`…) | `#[AsAdmin(resource:)]` (free-form, deep-merged; `null` removes a derived key, e.g. `'translation' => null`) |
| `vars` written in full (no derived default) | `#[AsAdmin(defaultVars: false, vars: …)]`      |

A translatable model gets `classes.factory: Sylius\Resource\Factory\TranslatableFactory` and a
`translation` sub-resource on the easy-crud controller and the Admin form, as the maker generates them.

The attribute routes are imported by `@SyliusEasyCrudPlugin/config/routes.yaml` under the Sylius admin
path (`/%sylius_admin.path_name%`). If you import `@SyliusEasyCrudPlugin/config/routes/admin.yaml`
directly (another prefix, a dedicated host), import the loader the same way, at the place of your former
`type: sylius.resource` blocks to keep the route order:

```yaml
sylius_easy_crud_attributes:
    resource: 'Adeliom\SyliusEasyCrudPlugin\Routing\EasyCrudAttributesRoutesLoader'
    type: service
    prefix: /admin
    host: '%admin_host%'
```

The model, grid name and form type are read from the Admin (`resourceClass`, or `getEntityFqcn()` when it is not
set; `getName()`; the Admin class). The repository is read from the model's `#[ORM\Entity(repositoryClass:)]` when it is
a Sylius resource repository (it implements `Sylius\Resource\Doctrine\Persistence\RepositoryInterface`). A plain
Doctrine repository (`ServiceEntityRepository`) is not set as `classes.repository`: the resource keeps the Sylius
default, whose `<application>.repository.<name>` service (used by the grid and the resource controller) is built
through the entity manager, `$entityManager->getRepository(Model::class)`. It is therefore the Doctrine repository of
the entity itself, which does not implement the Sylius `RepositoryInterface` (no `add()`, `remove()`,
`createPaginator()`): it must provide the grid method (`createListQueryBuilder` by default, see
`getRepositoryMethod()`). Set `#[AsAdmin(repository:)]` to use a Sylius resource repository instead.

The Admin static methods below can move to attribute arguments (or be dropped to use the defaults):

- `getDefaultSortColumn()`, `getDefaultSortOrder()`, `getLimits()`, `getRepositoryMethod()`: a kept method wins
  over the attribute;
- `getEntityFqcn()` and `getName()` are also read when the container is built (form data class, model and grid of
  the routes). When `resourceClass` / `grid` is set and `getEntityFqcn()` / `getName()` is overridden — by the Admin
  itself **or inherited from a parent Admin** (an application Admin extending a vendor one, such as the happy-cms
  `PageAdmin`) — both must return the same value, otherwise the container compilation fails with a `LogicException`
  naming the Admin, the class declaring the method and both values. An Admin extending a concrete Admin with another
  model must therefore override `getEntityFqcn()` (and usually `getName()`). `getName()` is always the grid name Sylius
  registers, and two Admins returning the same name fail the compilation (Sylius would keep only one of the grids).
- in the Admin code, read the model with `$this->getResourceClass()` rather than `static::getEntityFqcn()`.

| Admin static method      | `#[AsAdmin]` argument                        |
|--------------------------|----------------------------------------------|
| `getEntityFqcn()`        | `resourceClass`                              |
| `getName()`              | `grid` (or derived from the alias: `app_admin_post`) |
| `getDefaultSortColumn()` | `defaultSort`                                |
| `getDefaultSortOrder()`  | `defaultSortOrder`                           |
| `getLimits()`            | `limits`                                     |
| `getRepositoryMethod()`  | `repositoryMethod` + `repositoryArguments`   |

#### 4. Remove the legacy blocks

Delete the resource's `type: sylius.resource` block from `config/routes.yaml` and its
`sylius_resource.resources.<alias>` block from `config/packages/sylius_resource.yaml`.
Keeping the `sylius_resource.resources.<alias>` block next to `#[AsAdmin]` fails the container compilation
(instead of the YAML silently winning over the attribute). So does setting both `only:` and `except:` on `#[AsAdmin]`.

Two double declarations are **not** detected, check them by hand:

- a `type: sylius.resource` route block left in `config/routes.yaml`: the routing is not known when the container is
  built, so the block keeps generating the same route names as the attribute, and whichever is imported last wins
  silently. `bin/console debug:router <route>` shows which one is used;
- a `sylius_resource.resources.<alias>` block contributed by another bundle in its own `prepend()`, after easy-crud:
  easy-crud only sees the configuration declared before its own `prepend()` (the application files and the bundles
  registered before it).

#### 5. (Optional) Drop now-redundant boilerplate

`AbstractFormType` now implements `ServiceSubscriberInterface` with a default
`getSubscribedServices(): []`, so Admin classes no longer need to implement it
themselves. You can remove `implements ServiceSubscriberInterface` and the empty
`getSubscribedServices()` from your admins (keep them only if they subscribe to
specific services).

### Verifying

```bash
# Route names/paths must be identical before and after the migration:
bin/console debug:router | grep <your_resource>
# The resource is registered with the easy-crud controller:
bin/console debug:container <app>.controller.<name>   # => SyliusCrudResourceController
```

## 2.3.1

### Admin routes no longer hard-code `/admin`

The plugin admin routes (`sylius_easy_crud.get_oembed`, `sylius_admin_ajax_resources`) moved to
`config/routes/admin.yaml` and no longer contain `/admin` in their path.

`config/routes.yaml` is kept as a backward compatible entry point: it imports them with
`prefix: '/%sylius_admin.path_name%'`.

- **You import `@SyliusEasyCrudPlugin/config/routes.yaml` and use the default admin path (`/admin`)**:
  nothing changes, the routes stay `/admin/oembed/` and `/admin/autocomplete/`.
- **You changed `SYLIUS_ADMIN_ROUTING_PATH_NAME`**: the routes now follow it
  (e.g. `/back-office/oembed/`) and are therefore covered by the admin firewall.
  Templates and scripts generate them with `path()`, so no change is expected in your code
  unless you hard-coded `/admin/oembed/` or `/admin/autocomplete/`.
- **Your admin is served from another prefix or host**: import
  `@SyliusEasyCrudPlugin/config/routes/admin.yaml` directly with your own `prefix` / `host`.

`make:easy-crud:create-crud` now generates `prefix: '/%sylius_admin.path_name%'` instead of
`prefix: admin`. Routes generated before keep working as they are.

### `SortableCollectionType`: the submitted array is a list

`SortableCollectionType` (used by `SortableCollectionField`) now re-indexes the submitted array with
`array_values()`: after an entry is removed, or added with a non-sequential index, the value is `["A", "C"]` instead
of `{"0": "A", "2": "C"}`, so a JSON column stores an array (as EasyFields' sortable collection did). Order is unchanged;
only the keys change. Collection objects (Doctrine collections) are not affected.

To keep the keys (code that relies on them), set the new `keep_as_list` form option to `false`:

```php
SortableCollectionField::new('data')->setFormTypeOption('keep_as_list', false);
```
