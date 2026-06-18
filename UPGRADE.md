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
> #[AsEasyCrudAdmin] on its Admin class instead — see UPGRADE.md.

Both systems keep working side by side, so you can migrate **one resource at a time**.

### Automated migration (Claude Code skill)

This package ships a Claude Code skill that performs the migration for you:
**`.claude/skills/easy-crud-migrate-to-attributes/SKILL.md`**.

Copy that skill directory into your project's `.claude/skills/` (or your user-level
`~/.claude/skills/`), then ask Claude Code to *"migrate easy-crud resources to attributes"*
(or invoke `/easy-crud-migrate-to-attributes`). It finds the Admin classes, moves each YAML
declaration onto the Admin as `#[AsEasyCrudAdmin]`, removes the now-redundant
boilerplate, deletes the legacy YAML blocks, and verifies the routes are unchanged.

The manual steps below are what that skill automates.

### How to remove the deprecations

#### 1. Enable attribute discovery

```yaml
# config/packages/sylius_easy_crud.yaml
sylius_easy_crud:
    attributes:
        enabled: true
        paths: ['%kernel.project_dir%/src/Admin']  # where your Admin classes live
```

#### 2. Move the declaration onto the Admin class

Add `#[AsEasyCrudAdmin]` on the Admin and **keep the same `alias` + `section`** as the
legacy block, so route names and URLs stay byte-identical.

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsEasyCrudAdmin;

#[AsEasyCrudAdmin(
    entity: Cron::class,
    alias: 'app.log_cron',   // keep the legacy alias to preserve routes/URLs
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

Mapping from the legacy `resource:` block to attribute arguments:

| Legacy YAML                              | `#[AsEasyCrudAdmin]` argument                    |
|------------------------------------------|--------------------------------------------------|
| `alias`                                  | `alias`                                          |
| `section`                                | `section` (default `admin`)                      |
| `prefix` (route import)                  | `prefix` (default `admin`)                       |
| `templates`                              | `templates` (default easy-crud)                  |
| `grid`                                   | `getName()` (or `grid:` override)                |
| `form.type`                              | the Admin class itself (no argument)             |
| `except` / `only`                        | `except` / `only`                                |
| `redirect`                               | `redirect` (default `update`)                    |
| `permission`                             | `permission` (default `true`)                    |
| `vars.all.icon` / `header` / `subheader` | `icon` / `header` / `subheader` / `breadcrumb`   |
| anything else under `vars`               | `vars` (free-form passthrough, deep-merged)      |
| custom controller (`classes.controller`) | `controller`                                     |

The entity, grid name and form type are read from the Admin (`getEntityFqcn()`,
`getName()`, the Admin class). You may set them in the attribute (`entity:`, `grid:`)
or keep the static methods — **the static method wins over the attribute**.

#### 3. Remove the legacy blocks

Delete the resource's `type: sylius.resource` block from `config/routes.yaml` and its
`sylius_resource.resources.<alias>` block from `config/packages/sylius_resource.yaml`.

#### 4. (Optional) Drop now-redundant boilerplate

`AbstractFormType` now implements `ServiceSubscriberInterface` with a default
`getSubscribedServices(): []`, so Admin classes no longer need to implement it
themselves. You can remove `implements ServiceSubscriberInterface` and the empty
`getSubscribedServices()` from your admins (keep them only if they subscribe to
specific services).

### Verifying

```bash
# Route names/paths must be identical before and after the migration:
bin/console debug:router | grep <your_resource>
# The resource is registered:
bin/console debug:config sylius_resource
```
