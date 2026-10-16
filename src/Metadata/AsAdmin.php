<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

/**
 * Declares an easy-crud admin on its Admin class, on top of a Sylius resource
 * declared natively with #[\Sylius\Resource\Metadata\AsResource].
 *
 * The native #[AsResource] (placed on the entity) already registers the resource
 * model/driver into Sylius (sylius.resources). This attribute, placed on the
 * Admin, points back to that resource ($resourceClass) and lets easy-crud:
 *   - override the resource controller (=> SyliusCrudResourceController) and form
 *     (=> the Admin class) on the entry Sylius auto-registered;
 *   - build the legacy "type: sylius.resource" routing (grid, vars, $context,
 *     redirect, except/only) replayed by EasyCrudAttributesRoutesLoader.
 *
 * The override + routing are produced at container build time by
 * SyliusEasyCrudExtension::prepend() (see EasyCrudResourceFactory). Discovery is opt-in:
 * it only happens when `sylius_easy_crud.attributes.enabled` is true and the Admin
 * lives under one of the configured `attributes.paths`.
 *
 * Most values are optional: AbstractAdmin reads them as defaults for
 * getEntityFqcn()/getName()/getDefaultSortColumn()/… so an Admin can either carry
 * them here or override the corresponding static method. For getDefaultSortColumn(),
 * getDefaultSortOrder(), getLimits() and getRepositoryMethod() the method wins.
 * The resource class and the grid name are also read at container build time, through
 * AdminDeclarationResolver: an Admin declaring `resourceClass` or `grid` together with a
 * getEntityFqcn() or getName() returning another value, overridden by the Admin or inherited
 * from a parent Admin, fails the container compilation; so do two Admins whose getName()
 * returns the same grid name.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsAdmin
{
    /**
     * @param class-string|null    $resourceClass The class carrying #[AsResource] (usually the entity).
     *                                          Optional when the Admin overrides getEntityFqcn().
     * @param string|null          $alias      Explicit resource alias to target in sylius.resources. When null,
     *                                          the alias is resolved from the resource whose model === $resourceClass.
     *                                          Route names/URLs depend only on `alias` + `section`, and the alias is
     *                                          owned by #[AsResource] — set it there to keep them stable on migration.
     * @param string               $section    Sylius section ("admin" by default).
     * @param string               $prefix     Extra route prefix, relative to the import of the attribute routes (none by
     *                                          default: `@SyliusEasyCrudPlugin/config/routes.yaml` already serves them under
     *                                          the Sylius admin path, `/%sylius_admin.path_name%`).
     * @param string               $templates  Templates namespace passed to the resource routing.
     * @param class-string|null    $controller Controller FQCN. Defaults to easy-crud's SyliusCrudResourceController.
     * @param class-string|null    $repository Repository FQCN (`classes.repository`). Defaults to the model's
     *                                          #[ORM\Entity(repositoryClass:)] when it is a Sylius resource repository
     *                                          (implements Sylius\Resource\Doctrine\Persistence\RepositoryInterface),
     *                                          otherwise to the Sylius default.
     * @param string|null          $grid       Grid name override (feeds getName()). Defaults to the prefix of the Sylius
     *                                          route names, `<application>_<section>_<name>` (alias "app.book" =>
     *                                          "app_admin_book"). Must equal getName() when that method is overridden.
     * @param string|null          $defaultSort Default sort column (feeds getDefaultSortColumn()).
     * @param string|null          $defaultSortOrder Default sort order "asc"/"desc" (feeds getDefaultSortOrder()).
     * @param list<int>|null       $limits     Pagination limits (feeds getLimits()).
     * @param string|null          $repositoryMethod    Grid data repository method (feeds getRepositoryMethod()['method']).
     * @param list<mixed>|null     $repositoryArguments Arguments for that repository method (feeds getRepositoryMethod()['arguments']).
     * @param bool|null            $permission Whether the resource requires permission checks. Left null (omitted)
     *                                          by default so it matches the legacy behaviour (Sylius => false);
     *                                          set explicitly to enable app.<resource>.<action> permission checks.
     * @param list<string>         $except     Actions to exclude (index, create, update, show, delete, bulkDelete); an
     *                                          unknown action fails the container compilation.
     * @param list<string>         $only       Actions to restrict to, same validation. Setting both $only and $except
     *                                          fails the container compilation.
     * @param string               $redirect   Action to redirect to after create/update ("update" by default).
     * @param string|null          $icon       Semantic-UI icon for the index subheader. Note: on Sylius 2 the CRUD headers
     *                                          come from the Twig hooks (`<application>.ui.<plural name>` /
     *                                          `<application>.ui.<name>`): the icon/header/subheader/breadcrumb vars
     *                                          only reach templates reading `configuration.vars`.
     * @param string|null          $header     Header translation key (applied to index/create/update).
     * @param string|null          $subheader  Subheader translation key.
     * @param string|null          $breadcrumb Breadcrumb translation key.
     * @param array<string, mixed> $vars       Free-form vars passthrough, deep-merged over the derived defaults.
     *                                          Supports the per-action structure (all/index/update/...) including
     *                                          redirect/route/parameters using the $context / $id placeholders.
     * @param string|null          $path       Base URL path override (defaults to the urlized plural alias name).
     * @param bool                 $defaultVars Whether $vars is merged over the derived defaults (icon, subheader,
     *                                          breadcrumb, headers, update redirect/route). Set to false to use
     *                                          $icon/$header/$subheader/$breadcrumb and $vars only, as written in a
     *                                          legacy `resource:` block.
     * @param array<string, mixed> $resource   Free-form passthrough deep-merged over the derived Sylius resource entry
     *                                          (`sylius_resource.resources.<alias>`): `classes.factory`, the
     *                                          `translation` sub-resource classes, `state_machine_component`… A null
     *                                          value removes the derived key (e.g. `'translation' => null` for a
     *                                          translatable model managed without a translation sub-resource).
     */
    public function __construct(
        public ?string $resourceClass = null,
        public ?string $alias = null,
        public string $section = 'admin',
        public string $prefix = '',
        public string $templates = '@SyliusEasyCrudPlugin\\crud',
        public ?string $controller = null,
        public ?string $repository = null,
        public ?string $grid = null,
        public ?string $defaultSort = null,
        public ?string $defaultSortOrder = null,
        public ?array $limits = null,
        public ?string $repositoryMethod = null,
        public ?array $repositoryArguments = null,
        public ?bool $permission = null,
        public array $except = [],
        public array $only = [],
        public string $redirect = 'update',
        public ?string $icon = null,
        public ?string $header = null,
        public ?string $subheader = null,
        public ?string $breadcrumb = null,
        public array $vars = [],
        public ?string $path = null,
        public bool $defaultVars = true,
        public array $resource = [],
    ) {
    }
}
