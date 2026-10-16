<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Mate\Tool;

use Adeliom\SyliusEasyCrudPlugin\Admin\AdminInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\Mate\AdminReader;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AdminDeclarationResolver;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Sylius\MateExtension\Output\Envelope;
use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\Component\Form\FormInterface;

final class InspectAdmin
{
    /** The pages an Admin configures, in the order of the CRUD. */
    private const PAGES = [Crud::PAGE_INDEX, Crud::PAGE_NEW, Crud::PAGE_EDIT, Crud::PAGE_DETAIL];

    /** The pages rendering the form of the Admin. */
    private const FORM_PAGES = [Crud::PAGE_NEW, Crud::PAGE_EDIT];

    public function __construct(
        private readonly HostContainerProvider $host,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    #[MateTool(
        name: 'easy_crud_inspect_admin',
        description: 'Inspect one easy-crud resource by alias: the #[AsAdmin] arguments, then for each page (index, new, update, show) the fields declared by configureFields() and, for new/update, the form actually built (field name => form type). Filters of the grid too. Restrict to one page with "page". The actions depend on the request: not reported.',
    )]
    public function __invoke(string $alias, ?string $page = null): array
    {
        if (null !== $page && !\in_array($page, self::PAGES, true)) {
            return Envelope::error('invalid_page', sprintf('Unknown page "%s", expected one of: %s.', $page, implode(', ', self::PAGES)));
        }

        return Envelope::guard(fn (): array => $this->inspect($alias, $page));
    }

    /**
     * @return array<string, mixed>
     */
    private function inspect(string $alias, ?string $page): array
    {
        $reader = new AdminReader($this->host->getContainer());
        $description = $reader->get($alias);
        if (null === $description) {
            return Envelope::empty(sprintf('"%s" is not an easy-crud resource. Call easy_crud_list_admins to find it.', $alias));
        }

        $adminClass = $description['admin'];
        $admin = $reader->admin($adminClass);

        $pages = [];
        foreach (null === $page ? self::PAGES : [$page] as $pageName) {
            $pages[$pageName] = $this->describePage($reader, $admin, $pageName);
        }

        return Envelope::items([[
            ...$description,
            'attribute' => $this->attributeArguments($adminClass),
            'pages' => $pages,
            'filters' => $this->guarded(fn (): array => $this->filters($admin)),
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function describePage(AdminReader $reader, AdminInterface $admin, string $pageName): array
    {
        $description = [
            'fields' => $this->guarded(fn (): array => $this->fields($admin, $pageName)),
        ];

        if (\in_array($pageName, self::FORM_PAGES, true)) {
            $description['form'] = $this->guarded(fn (): array => $this->builtForm($reader->createForm($admin::class, $pageName)));
        }

        return $description;
    }

    /**
     * The fields as declared, before the field configurators run: the form type is often resolved later (see "form").
     *
     * @return list<array<string, mixed>>
     */
    private function fields(AdminInterface $admin, string $pageName): array
    {
        $fields = [];
        foreach ($admin->configureFields($pageName) as $field) {
            $dto = $field->getAsDto();
            if (!$dto->isDisplayedOn($pageName)) {
                continue;
            }

            $fields[] = array_filter([
                'property' => $dto->getProperty(),
                'field' => $dto->getFieldFqcn(),
                'label' => $dto->getLabel(),
                'form_type' => $dto->getFormType(),
                'virtual' => $dto->isVirtual() ?: null,
                'permission' => $dto->getPermission(),
            ], static fn (mixed $value): bool => null !== $value);
        }

        return $fields;
    }

    /**
     * @param FormInterface<mixed> $form
     *
     * @return array<string, array<string, mixed>>
     */
    private function builtForm(FormInterface $form): array
    {
        $children = [];
        foreach ($form as $name => $child) {
            $config = $child->getConfig();
            $children[(string) $name] = array_filter([
                'type' => $config->getType()->getInnerType()::class,
                'required' => $config->getRequired(),
                'disabled' => $config->getDisabled() ?: null,
                'mapped' => $config->getMapped() ? null : false,
            ], static fn (mixed $value): bool => null !== $value);
        }

        return $children;
    }

    /**
     * @return list<array{name: string, type: mixed}>
     */
    private function filters(AdminInterface $admin): array
    {
        $filters = [];
        foreach ($admin->configureFilters() as $filter) {
            $filters[] = [
                'name' => $filter->getName(),
                'type' => $filter->toArray()['type'] ?? null,
            ];
        }

        return $filters;
    }

    /**
     * The #[AsAdmin] arguments that differ from their default, null when the Admin has no attribute.
     *
     * @param class-string $adminClass
     *
     * @return array<string, mixed>|null
     */
    private function attributeArguments(string $adminClass): ?array
    {
        $attribute = AdminDeclarationResolver::attribute($adminClass);
        if (null === $attribute) {
            return null;
        }

        $defaults = new AsAdmin();
        $arguments = [];
        foreach ((new \ReflectionClass(AsAdmin::class))->getProperties() as $property) {
            $value = $property->getValue($attribute);
            if ($value !== $property->getValue($defaults)) {
                $arguments[$property->getName()] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Runs one part of the inspection, reporting its failure in place so that the other parts are still returned:
     * an exception thrown while building the fields or the form is often the bug being investigated.
     *
     * @param callable(): array<mixed> $part
     *
     * @return array<mixed>
     */
    private function guarded(callable $part): array
    {
        try {
            return $part();
        } catch (\Throwable $exception) {
            return ['error' => sprintf('%s: %s', $exception::class, $exception->getMessage())];
        }
    }
}
