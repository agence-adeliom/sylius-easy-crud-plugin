# Upgrade

## Unreleased (2.3.x)

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
