---
name: easy-crud-migrate-to-attributes
description: Migrate Adeliom Sylius easy-crud resources from the legacy YAML declaration (config/routes.yaml `type: sylius.resource` + config/packages/sylius_resource.yaml) to the `#[AsResource]` (on the model) + `#[AsAdmin]` (on the Admin) attributes. Use when upgrading easy-crud (2.1+) and you see deprecations about "Declaring the easy-crud resource ... through legacy YAML", or when asked to migrate easy-crud admins to attributes / remove easy-crud YAML resource blocks.
---

# Migrate easy-crud resources to attributes (`#[AsResource]` + `#[AsAdmin]`)

Since easy-crud 2.1, an admin resource is declared with **two attributes** instead of the two
legacy YAML blocks:

- **`#[\Sylius\Resource\Metadata\AsResource]` on the model (entity)** — declares the native Sylius
  resource identity (its `alias`).
- **`#[Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin]` on the Admin class** — the easy-crud layer:
  points to the model via `resourceClass:` and carries the admin UI/routing metadata.

This skill performs that migration safely, one resource at a time.

## Why two attributes (and why it's wired in `prepend()`)

Sylius auto-registers each `#[AsResource]` with the **default** `ResourceController` /
`DefaultResourceType` form, and the resource driver builds the per-resource services (controller,
repository, translation sub-resource) **during the container `load()` phase — before any compiler
pass**. easy-crud therefore cannot "patch" the resource afterwards (the controller would stay
`ResourceController`, no translation sub-resource would exist). Instead it contributes the **full**
resource config from its bundle `prepend()`, keyed by the `#[AsResource]` alias; Sylius' own
auto-registration skips an already-declared alias, so easy-crud owns the entry. You don't need to do
anything for this — just know that `#[AsResource]` provides the alias and easy-crud provides the rest.

## Golden rules

- **Preserve `alias` + `section`.** Route names and URLs are derived only from these. Keep
  them identical or you break links, redirects and templates. The alias lives on `#[AsResource]`.
- **Preserve the grid name.** It is `Admin::getName()`; the grid is registered under it.
  Capture it into the attribute (`grid:`) before deleting the method.
- **One resource at a time, then verify.** The legacy and attribute systems coexist, so
  migrate, run the verification, then move on.
- **Never invent values.** Only move what already exists in the YAML / Admin into the attributes.

## Prerequisites

1. `agence-adeliom/sylius-easy-crud-plugin` is `>= 2.1`.
2. Enable discovery (once for the project) — **two** path lists:

   ```yaml
   # config/packages/sylius_easy_crud.yaml
   sylius_easy_crud:
       attributes:
           enabled: true
           paths: ['%kernel.project_dir%/src/Admin']   # dirs holding the #[AsAdmin] classes

   # config/packages/sylius_resource.yaml
   sylius_resource:
       mapping:
           paths: ['%kernel.project_dir%/src/Entity']  # dirs holding the #[AsResource] models
   ```

   If admins/models live elsewhere (e.g. a plugin namespace), list every directory.

## Step 1 — Find the Admin classes

Find every class extending `Adeliom\SyliusEasyCrudPlugin\Admin\AbstractAdmin`:

```bash
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
  Note its `classes.model` (the entity) and `alias`.

The shared key between them is the **alias**.

```bash
grep -n "PostAdmin" config/routes.yaml config/packages/sylius_resource.yaml
```

## Step 3 — Declare the model as a `#[AsResource]`

On the entity (`classes.model`), add the native attribute, keeping the legacy alias:

```php
use Sylius\Resource\Metadata\AsResource;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[AsResource(alias: 'app.post')]   // the legacy alias — keep it verbatim
class Post implements ResourceInterface
{
    // ...
}
```

Idempotent: skip if the class already has `#[AsResource]`. Ensure the entity's directory is in
`sylius_resource.mapping.paths`.

## Step 4 — Map the YAML to `#[AsAdmin]` arguments

Read the route block and build `#[AsAdmin(...)]` using this mapping. Omit any argument
that already equals the easy-crud default.

| Legacy YAML (route block unless noted)          | `#[AsAdmin]` argument         | Default (omit if equal)            |
|-------------------------------------------------|-------------------------------|------------------------------------|
| `classes.model`                                 | `resourceClass:`              | — (always set)                     |
| `alias`                                         | `alias:`                      | the model's `#[AsResource]` alias  |
| `section`                                       | `section:`                    | `'admin'`                          |
| `prefix` (import level)                         | `prefix:`                     | `'admin'`                          |
| `templates`                                     | `templates:`                  | `'@SyliusEasyCrudPlugin\crud'`     |
| `grid` (= `Admin::getName()`)                   | `grid:`                       | derived `admin_<snake(short name without "Admin")>` |
| `form.type`                                     | (the Admin itself — no arg)   | —                                  |
| `except` / `only`                               | `except:` / `only:`           | `[]`                               |
| `redirect`                                      | `redirect:`                   | `'update'`                         |
| `permission`                                    | `permission:`                 | omitted (Sylius => `false`)        |
| `vars.all.icon` (or `vars.index.icon`)          | `icon:`                       | `'file'`                           |
| `vars.*.header`                                 | `header:`                     | default i18n keys                  |
| `vars.all.subheader`                            | `subheader:`                  | default i18n keys                  |
| `vars.all.breadcrumb`                           | `breadcrumb:`                 | default i18n keys                  |
| `path` (if a custom URL was set)                | `path:`                       | derived from plural alias          |
| `classes.controller` (if not SyliusCrudResourceController) | `controller:`      | `SyliusCrudResourceController`     |
| any other `vars` (per-action overrides, custom keys) | `vars:` (free-form, deep-merged) | `[]`                          |

Notes:
- Setting `alias:` on `#[AsAdmin]` is optional (it defaults to the model's `#[AsResource]` alias).
  Keep them equal. The alias is what preserves routes — make sure `#[AsResource(alias:)]` is right.
- If a single `header` is used for index/create/update, pass `header:`. If they differ, keep
  them under `vars:` (e.g. `vars: ['create' => ['header' => '...']]`).
- The standard per-action `update.redirect` / `update.route` blocks with `$context`/`$id` are
  **regenerated automatically** by the attribute — do not copy them unless they are customized.

## Step 5 — Rewrite the Admin class

Apply, in order:

1. **Add the attribute** above the class, with `use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;`.
   - Always set `resourceClass:` (the model, e.g. `Post::class`).
   - Set `grid:` to the exact value `getName()` returned, **unless** it already equals the
     derived name `admin_<snake(class short name without the "Admin" suffix)>` — then omit it.
2. **Remove `public static function getEntityFqcn()`** — now provided by `resourceClass:`.
3. **Remove `public static function getName()`** — now provided by `grid:` (or derivation).
4. **`getDefaultSortColumn()`**: if it returns `''`, remove it; otherwise move the value to
   `defaultSort:` and remove the method. Same idea for `getDefaultSortOrder()` → `defaultSortOrder:`,
   `getLimits()` → `limits:`, and `getRepositoryMethod()` → `repositoryMethod:` + `repositoryArguments:`
   (only if they were overridden; the easy-crud defaults are otherwise kept).
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
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;

#[AsAdmin(
    resourceClass: Post::class,
    alias: 'app.post',     // optional — equals the model's #[AsResource] alias
    icon: 'newspaper',
    except: ['show'],
    // grid: 'admin_post' omitted — equals the derived name
)]
final class PostAdmin extends AbstractAdmin
{
    public function configureFields(string $pageName, ?string $context = null): iterable { /* ... */ }
}
```

## Step 6 — Remove the legacy YAML blocks

Delete:
- the `type: sylius.resource` entry for this resource in `config/routes.yaml`;
- the `sylius_resource.resources.<alias>` entry in `config/packages/sylius_resource.yaml`
  (including its `translation:` sub-block, if any — translations are handled automatically).

Leave every other (not-yet-migrated) resource untouched.

## Step 7 — Verify

```bash
# 1. Snapshot routes before (do this BEFORE deleting the YAML if migrating incrementally):
bin/console debug:router | grep <alias_last_segment> | sort > /tmp/before.txt

# 2. Clear cache and re-snapshot:
bin/console cache:clear
bin/console debug:router | grep <alias_last_segment> | sort > /tmp/after.txt

# 3. Diff must be empty (identical names, paths, methods):
diff /tmp/before.txt /tmp/after.txt

# 4. The resource uses the easy-crud controller (NOT the default ResourceController):
bin/console debug:container <app>.controller.<name>   # => SyliusCrudResourceController
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
