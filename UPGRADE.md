# Upgrade Guide

## Upgrading to 2.1

### Deprecation: declaring resources via YAML

Declaring an easy-crud resource through the generated YAML blocks — the
`type: sylius.resource` block in `config/routes.yaml` **and** the
`sylius_resource.resources.<alias>` block in `config/packages/sylius_resource.yaml` —
is **deprecated** and will be **removed in 3.0**.

After upgrading, a deprecation is triggered (at container build) for every easy-crud
resource still declared this way, e.g.:

> Declaring the easy-crud resource "app.log_cron" through legacy YAML (sylius_resource
> + "type: sylius.resource") is deprecated and will be removed in 3.0. Declare it with
> #[AsAdmin] on its Admin class instead — see UPGRADE.md.

Both systems keep working side by side, so you can migrate **one resource at a time**.

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

#### 2. Declare the model as a resource

Add `#[AsResource]` on the entity and **keep the legacy alias** so route names and URLs stay
byte-identical.

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
`#[AsResource]`. When set, it must match an existing resource alias.

Mapping from the legacy `resource:` block to attribute arguments:

| Legacy YAML                              | New attribute argument                           |
|------------------------------------------|--------------------------------------------------|
| `alias`                                  | `#[AsResource(alias:)]` (read by `#[AsAdmin]`)   |
| `classes.model`                          | `#[AsAdmin(resourceClass:)]`                     |
| `section`                                | `#[AsAdmin(section:)]` (default `admin`)         |
| `prefix` (route import)                  | `#[AsAdmin(prefix:)]` (default `admin`)          |
| `templates`                              | `#[AsAdmin(templates:)]` (default easy-crud)     |
| `grid`                                   | `getName()` (or `#[AsAdmin(grid:)]` override)    |
| `form.type`                              | the Admin class itself (no argument)             |
| `except` / `only`                        | `#[AsAdmin(except:)]` / `only:`                  |
| `redirect`                               | `#[AsAdmin(redirect:)]` (default `update`)       |
| `permission`                             | `#[AsAdmin(permission:)]` (default: omitted)     |
| `vars.all.icon` / `header` / `subheader` | `icon` / `header` / `subheader` / `breadcrumb`   |
| anything else under `vars`               | `#[AsAdmin(vars:)]` (free-form, deep-merged)     |
| custom controller (`classes.controller`) | `#[AsAdmin(controller:)]`                        |

The model, grid name and form type are read from the Admin (`getEntityFqcn()` returns
`resourceClass`, `getName()`, the Admin class). The repository is read from the model's
`#[ORM\Entity(repositoryClass:)]`. The Admin static methods below can move to attribute
arguments (or be dropped to use the defaults) — **a kept method always wins over the
attribute**:

| Admin static method      | `#[AsAdmin]` argument                        |
|--------------------------|----------------------------------------------|
| `getEntityFqcn()`        | `resourceClass`                              |
| `getName()`              | `grid` (or derived from the class name)      |
| `getDefaultSortColumn()` | `defaultSort`                                |
| `getDefaultSortOrder()`  | `defaultSortOrder`                           |
| `getLimits()`            | `limits`                                     |
| `getRepositoryMethod()`  | `repositoryMethod` + `repositoryArguments`   |

#### 4. Remove the legacy blocks

Delete the resource's `type: sylius.resource` block from `config/routes.yaml` and its
`sylius_resource.resources.<alias>` block from `config/packages/sylius_resource.yaml`.

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
