---
name: easy-crud-migrate-to-attributes
description: Migrate Adeliom Sylius easy-crud resources from the legacy YAML declaration (config/routes.yaml `type: sylius.resource` + config/packages/sylius_resource.yaml) to the `#[AsEasyCrudAdmin]` attribute on the Admin class. Use when upgrading easy-crud (2.1+) and you see deprecations about "Declaring the easy-crud resource ... through legacy YAML", or when asked to migrate easy-crud admins to attributes / remove easy-crud YAML resource blocks.
---

# Migrate easy-crud resources to `#[AsEasyCrudAdmin]`

Since easy-crud 2.1, an admin resource is declared with a single `#[AsEasyCrudAdmin]`
attribute on its **Admin class**, replacing the two legacy YAML blocks. This skill
performs that migration safely, one resource at a time.

## Golden rules

- **Preserve `alias` + `section`.** Route names and URLs are derived only from these. Keep
  them identical or you break links, redirects and templates.
- **Preserve the grid name.** It is `Admin::getName()`; the grid is registered under it.
  Capture it into the attribute (`grid:`) before deleting the method.
- **One resource at a time, then verify.** The legacy and attribute systems coexist, so
  migrate, run the verification, then move on.
- **Never invent values.** Only move what already exists in the YAML / Admin into the attribute.

## Prerequisites

1. `agence-adeliom/sylius-easy-crud-plugin` is `>= 2.1`.
2. Enable attribute discovery (once for the project):

   ```yaml
   # config/packages/sylius_easy_crud.yaml
   sylius_easy_crud:
       attributes:
           enabled: true
           paths: ['%kernel.project_dir%/src/Admin']  # all dirs holding annotated Admins
   ```

   If admins live elsewhere (e.g. `src/Admin` + a plugin namespace), list every directory.

## Step 1 — Find the Admin classes

Find every class extending `Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin`:

```bash
grep -rln "extends AbstractAdmin" src/ | xargs grep -l "Adeliom\\\\SyliusEasyCrudPlugin\\\\Admin"
# or, more broadly, search the use + extends pair:
grep -rln "use Adeliom\\\\SyliusEasyCrudPlugin\\\\Admin\\\\AbstractAdmin" src/
```

Process each one. (Abstract base admins — e.g. `AbstractXxxAdmin` — are skipped; migrate only the concrete ones that have a YAML declaration.)

## Step 2 — Locate the legacy declaration for that Admin

For a given Admin `App\Admin\PostAdmin`, find its two legacy blocks:

- **Route block** in `config/routes.yaml`: a `type: sylius.resource` entry whose embedded
  `resource:` string has `form.type: App\Admin\PostAdmin` (the form type **is** the Admin).
  It also carries `prefix:` at the import level.
- **Resource block** in `config/packages/sylius_resource.yaml`: the
  `sylius_resource.resources.<alias>` entry whose `classes.form` is `App\Admin\PostAdmin`.

The shared key between them is the **alias**.

```bash
grep -n "PostAdmin" config/routes.yaml config/packages/sylius_resource.yaml
```

## Step 3 — Map the YAML to attribute arguments

Read the route block and build `#[AsEasyCrudAdmin(...)]` using this mapping. Omit any argument
that already equals the easy-crud default.

| Legacy YAML (route block unless noted)          | `#[AsEasyCrudAdmin]` argument | Default (omit if equal)            |
|-------------------------------------------------|-------------------------------|------------------------------------|
| `Admin::getEntityFqcn()` return / `classes.model` | `entity:`                   | — (always set; see Step 4)         |
| `alias`                                         | `alias:`                      | — (always set to preserve routes)  |
| `section`                                       | `section:`                    | `'admin'`                          |
| `prefix` (import level)                         | `prefix:`                     | `'admin'`                          |
| `templates`                                     | `templates:`                  | `'@SyliusEasyCrudPlugin\crud'`     |
| `grid` (= `Admin::getName()`)                   | `grid:`                       | derived `admin_<snake(short name without "Admin")>` |
| `form.type`                                     | (the Admin itself — no arg)   | —                                  |
| `except` / `only`                               | `except:` / `only:`           | `[]`                               |
| `redirect`                                      | `redirect:`                   | `'update'`                         |
| `permission`                                    | `permission:`                 | `true`                             |
| `vars.all.icon` (or `vars.index.icon`)          | `icon:`                       | `'file'`                           |
| `vars.*.header`                                 | `header:`                     | default i18n keys                  |
| `vars.all.subheader`                            | `subheader:`                  | default i18n keys                  |
| `vars.all.breadcrumb`                           | `breadcrumb:`                 | default i18n keys                  |
| `path` (if a custom URL was set)                | `path:`                       | derived from plural alias          |
| `classes.controller` (if not SyliusCrudResourceController) | `controller:`      | `SyliusCrudResourceController`     |
| any other `vars` (per-action overrides, custom keys) | `vars:` (free-form, deep-merged) | `[]`                          |

Notes:
- If a single `header` is used for index/create/update, pass `header:`. If they differ, keep
  them under `vars:` (e.g. `vars: ['create' => ['header' => '...']]`).
- The standard per-action `update.redirect` / `update.route` blocks with `$context`/`$id` are
  **regenerated automatically** by the attribute — do not copy them unless they are customized.

## Step 4 — Rewrite the Admin class

Apply, in order:

1. **Add the attribute** above the class, with `use Adeliom\SyliusEasyCrudPlugin\Metadata\AsEasyCrudAdmin;`.
   - Always set `entity:` (the value `getEntityFqcn()` returned, e.g. `Post::class`).
   - Set `grid:` to the exact value `getName()` returned, **unless** it already equals the
     derived name `admin_<snake(class short name without the "Admin" suffix)>` — then omit it.
2. **Remove `public static function getEntityFqcn()`** — now provided by `entity:`.
3. **Remove `public static function getName()`** — now provided by `grid:` (or derivation).
4. **`getDefaultSortColumn()`**: if it returns `''`, remove it; otherwise move the value to
   `defaultSort:` and remove the method. Same idea for `getDefaultSortOrder()` → `defaultSortOrder:`
   and `getLimits()` → `limits:` if they were overridden.
5. **`ServiceSubscriberInterface`**: if the class `implements ServiceSubscriberInterface` **and**
   `getSubscribedServices()` returns `[]` (empty), remove both the method and the interface (and its
   `use`). If `getSubscribedServices()` returns real services, keep them.
6. **Remove `implements AdminInterface`** (and its `use`) — `AbstractAdmin` already implements it.

### Before

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class PostAdmin extends AbstractAdmin implements ServiceSubscriberInterface, AdminInterface
{
    public static function getSubscribedServices(): array { return []; }
    public static function getName(): string { return 'admin_post'; }
    public static function getEntityFqcn(): string { return Post::class; }
    public static function getDefaultSortColumn(): string { return ''; }

    public function configureFields(string $pageName, ?string $context = null): iterable { /* ... */ }
}
```

### After

```php
use Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsEasyCrudAdmin;

#[AsEasyCrudAdmin(
    entity: Post::class,
    alias: 'app.post',     // the legacy alias — keep it
    icon: 'newspaper',
    except: ['show'],
    // grid: 'admin_post' omitted — equals the derived name
)]
final class PostAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable { /* ... */ }
}
```

## Step 5 — Remove the legacy YAML blocks

Delete:
- the `type: sylius.resource` entry for this resource in `config/routes.yaml`;
- the `sylius_resource.resources.<alias>` entry in `config/packages/sylius_resource.yaml`
  (including its `translation:` sub-block, if any — translations are handled automatically).

Leave every other (not-yet-migrated) resource untouched.

## Step 6 — Verify

```bash
# 1. Snapshot routes before (do this BEFORE deleting the YAML if migrating incrementally):
bin/console debug:router | grep <alias_last_segment> | sort > /tmp/before.txt

# 2. Clear cache and re-snapshot:
bin/console cache:clear
bin/console debug:router | grep <alias_last_segment> | sort > /tmp/after.txt

# 3. Diff must be empty (identical names, paths, methods):
diff /tmp/before.txt /tmp/after.txt

# 4. The resource is still registered:
bin/console debug:config sylius_resource | grep <alias>
```

Then open the admin screens (index, create, edit, delete) in the browser to confirm the grid,
form and redirects still work. The deprecation for this resource must disappear:

```bash
bin/console debug:container --deprecations | grep <alias>   # → no result
```

If the project uses DDEV, prefix the commands with `ddev` (e.g. `ddev bin/console ...`).

## Repeat

Go back to Step 2 for the next Admin until `bin/console debug:container --deprecations`
reports no more easy-crud legacy-YAML deprecations.
