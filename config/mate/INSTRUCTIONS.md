## agence-adeliom/sylius-easy-crud-plugin — Agent Instructions

Read-only Mate tools exposing the easy-crud resources of the application, as the booted `dev` kernel sees them. They
need `sylius/sylius-mate-extension` (it boots the kernel): call them like the `sylius_*` tools.

| Tool | Use when |
|---|---|
| `easy_crud_list_admins` | Finding the Admin of a back-office screen, or checking how a resource is declared: alias, Admin class, model, controller, `declaration` (`attribute` for `#[AsAdmin]`, `yaml` for a legacy `sylius_resource` block), grid name and generated routes. `--alias_prefix=app.` filters |
| `easy_crud_inspect_admin` | Debugging one CRUD (`--alias=app.book`): the non-default `#[AsAdmin]` arguments, the fields of each page (`index`, `new`, `update`, `show`) as declared by `configureFields()`, the form actually built for `new` / `update` (field name => form type, `required`, `mapped: false`) and the grid filters. `--page=update` restricts the output |

- Compare `pages.update.fields` with `pages.update.form` to find a field filtered out while the form is built
  (permission, display condition, configurator). The layout fields (tabs, panels, columns, such as `_panel_main`)
  never reach `form`, and a dotted property (`footer.title`) is named with underscores there (`footer_title`).
- `form_type` in `fields` is the declared one, often null: the field configurators resolve it later, read `form`.
- A part that fails is reported in place as `{"error": "<exception>: <message>"}` while the other parts are still
  returned: this exception is often the bug. Building the form needs the database (choices of the relations).
- The actions (buttons) depend on the HTTP request and are not reported: open the page, then read its profile with
  `symfony-profiler-list` / `symfony-profiler://profile/<token>/exception`.
