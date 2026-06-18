<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Metadata;

/**
 * Declares an easy-crud admin resource directly on its Admin class, as an
 * alternative to the legacy `type: sylius.resource` block in config/routes.yaml
 * plus the matching `sylius_resource.resources.<alias>` entry.
 *
 * The attribute lives on the Admin (the presentation layer that already defines
 * the fields, grid and form) — not on the Doctrine entity, which stays a pure
 * domain model. It carries the resource "data" (which entity, section, icon,
 * routing behaviour…), while the Admin methods keep the "logic"
 * (configureFields/Actions/Filters).
 *
 * Most values are optional: AbstractAdmin reads them as defaults for
 * getEntityFqcn()/getName()/getDefaultSortColumn()/… so an Admin can either
 * carry them here or override the corresponding static method (the method wins).
 *
 * It is translated, at container build time, into the very same legacy resource
 * configuration and `sylius.resource` routes that the two YAML blocks used to
 * produce (see EasyCrudResourceFactory). Discovery is opt-in: it only happens
 * when `sylius_easy_crud.attributes.enabled` is true and the Admin lives under
 * one of the configured `attributes.paths`.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsEasyCrudAdmin
{
    /**
     * @param class-string|null    $entity     Model FQCN. Optional when the Admin overrides getEntityFqcn().
     * @param string|null          $alias      Resource alias (e.g. "app.log_cron"). Defaults to "app.<entity_short>".
     *                                          Route names/URLs depend only on `alias` + `section`, so set it
     *                                          explicitly to keep them stable when migrating an existing resource.
     * @param string               $section    Sylius section ("admin" by default).
     * @param string               $prefix     Route prefix ("admin" by default).
     * @param string               $templates  Templates namespace passed to the resource routing.
     * @param class-string|null    $controller Controller FQCN. Defaults to easy-crud's SyliusCrudResourceController.
     * @param string|null          $grid       Grid name override. Defaults to the Admin's getName().
     * @param string|null          $defaultSort Default sort column (feeds getDefaultSortColumn()).
     * @param string|null          $defaultSortOrder Default sort order "asc"/"desc" (feeds getDefaultSortOrder()).
     * @param list<int>|null       $limits     Pagination limits (feeds getLimits()).
     * @param string|null          $repositoryMethod    Grid data repository method (feeds getRepositoryMethod()['method']).
     * @param list<mixed>|null     $repositoryArguments Arguments for that repository method (feeds getRepositoryMethod()['arguments']).
     * @param bool                 $permission Whether the resource requires permission checks.
     * @param list<string>         $except     Actions to exclude (index, create, update, show, delete, bulkDelete).
     * @param list<string>         $only       Actions to restrict to (mutually exclusive with $except).
     * @param string               $redirect   Action to redirect to after create/update ("update" by default).
     * @param string|null          $icon       Semantic-UI icon for the index subheader.
     * @param string|null          $header     Header translation key (applied to index/create/update).
     * @param string|null          $subheader  Subheader translation key.
     * @param string|null          $breadcrumb Breadcrumb translation key.
     * @param array<string, mixed> $vars       Free-form vars passthrough, deep-merged over the derived defaults.
     *                                          Supports the per-action structure (all/index/update/...) including
     *                                          redirect/route/parameters using the $context / $id placeholders.
     * @param string|null          $path       Base URL path override (defaults to the urlized plural alias name).
     */
    public function __construct(
        public ?string $entity = null,
        public ?string $alias = null,
        public string $section = 'admin',
        public string $prefix = 'admin',
        public string $templates = '@SyliusEasyCrudPlugin\\crud',
        public ?string $controller = null,
        public ?string $grid = null,
        public ?string $defaultSort = null,
        public ?string $defaultSortOrder = null,
        public ?array $limits = null,
        public ?string $repositoryMethod = null,
        public ?array $repositoryArguments = null,
        public bool $permission = true,
        public array $except = [],
        public array $only = [],
        public string $redirect = 'update',
        public ?string $icon = null,
        public ?string $header = null,
        public ?string $subheader = null,
        public ?string $breadcrumb = null,
        public array $vars = [],
        public ?string $path = null,
    ) {
    }
}
