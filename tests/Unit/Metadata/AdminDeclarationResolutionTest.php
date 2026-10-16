<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\ConsistentAdmin\ConsistentAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\DivergentGrid\DivergentGridAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\DivergentResourceClass\DivergentResourceClassAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedAdmin\AppBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\InheritedOverriddenAdmin\OverridingBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\NoAttribute\BlogPostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\NoAttribute\FAQAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\SharedGridName\ChildBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\SharedGridName\ParentBookAdmin;

/**
 * The resource class and the grid name of an Admin are read the same way by the container (form data class), the
 * attribute resources (model, grid of the routes) and the Admin itself at runtime (grid, resource context).
 */
final class AdminDeclarationResolutionTest extends TestCase
{
    #[Test]
    public function a_resource_class_diverging_from_the_get_entity_fqcn_inherited_from_a_parent_admin_fails_the_attribute_discovery(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/AppBookAdmin.*Book.*getEntityFqcn\(\) inherited from .*VendorBookAdmin.*Magazine.*Override getEntityFqcn\(\)/s');

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/InheritedAdmin']);
    }

    #[Test]
    public function a_resource_class_diverging_from_the_get_entity_fqcn_inherited_from_a_parent_admin_fails_the_container_compilation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/AppBookAdmin.*inherited from .*VendorBookAdmin/s');

        $this->compile(AppBookAdmin::class);
    }

    #[Test]
    public function a_child_admin_overriding_the_get_entity_fqcn_of_its_parent_gets_its_own_model_everywhere(): void
    {
        $descriptor = EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/InheritedOverriddenAdmin'])[0];
        $admin = (new \ReflectionClass(OverridingBookAdmin::class))->newInstanceWithoutConstructor();

        self::assertSame(Book::class, $descriptor['registry']['classes']['model']);
        self::assertSame(Book::class, $this->formDataClassOf(OverridingBookAdmin::class));
        self::assertSame(Book::class, $admin->getResourceClass());
        self::assertSame(Book::class, OverridingBookAdmin::getEntityFqcn());
    }

    #[Test]
    public function a_grid_diverging_from_the_get_name_inherited_from_a_parent_admin_fails_with_a_message_naming_the_parent(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/AppGridBookAdmin.*app_admin_book.*getName\(\) inherited from .*VendorGridBookAdmin.*vendor_book.*Override getName\(\)/s');

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/InheritedGrid']);
    }

    #[Test]
    public function two_admins_using_the_same_grid_name_fail_the_container_compilation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/"shared_book".*ParentBookAdmin.*ChildBookAdmin/s');

        $this->compile(ParentBookAdmin::class, ChildBookAdmin::class);
    }

    #[Test]
    public function the_same_admin_class_registered_under_two_service_ids_is_not_a_grid_name_conflict(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(ParentBookAdmin::class, (new Definition(ParentBookAdmin::class))->addTag('sylius_easy_crud_admin'));
        $container->setDefinition('app.admin.book', (new Definition(ParentBookAdmin::class))->addTag('sylius_easy_crud_admin'));

        (new RegisterAdminPass())->process($container);

        self::assertSame(Book::class, $container->getDefinition('app.admin.book')->getArgument(0));
    }

    #[Test]
    public function an_attribute_and_methods_declaring_the_same_values_are_accepted(): void
    {
        $descriptor = EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/ConsistentAdmin'])[0];

        self::assertSame(Book::class, $descriptor['registry']['classes']['model']);
        self::assertSame('custom_book', $descriptor['routing']['config']['grid']);
        self::assertSame(Book::class, $this->formDataClassOf(ConsistentAdmin::class));
    }

    #[Test]
    public function a_resource_class_overridden_with_another_value_than_the_attribute_fails_the_attribute_discovery(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/DivergentResourceClassAdmin.*getEntityFqcn\(\).*Magazine.*resourceClass.*Book/s');

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/DivergentResourceClass']);
    }

    #[Test]
    public function a_resource_class_overridden_with_another_value_than_the_attribute_fails_the_container_compilation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/DivergentResourceClassAdmin/');

        $this->formDataClassOf(DivergentResourceClassAdmin::class);
    }

    #[Test]
    public function a_grid_name_overridden_with_another_value_than_the_attribute_fails_the_attribute_discovery(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/DivergentGridAdmin.*getName\(\).*custom_book.*grid.*app_admin_book/s');

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/DivergentGrid']);
    }

    #[Test]
    public function a_grid_name_overridden_with_another_value_than_the_attribute_fails_the_container_compilation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/DivergentGridAdmin/');

        $this->formDataClassOf(DivergentGridAdmin::class);
    }

    #[Test]
    public function without_attribute_the_grid_name_is_derived_from_the_admin_class_name_in_snake_case(): void
    {
        self::assertSame('admin_faq', FAQAdmin::getName());
        self::assertSame('admin_blog_post', BlogPostAdmin::getName());
    }

    /**
     * @param class-string $adminClass
     */
    private function formDataClassOf(string $adminClass): mixed
    {
        return $this->compile($adminClass)->getDefinition($adminClass)->getArgument(0);
    }

    /**
     * @param class-string ...$adminClasses
     */
    private function compile(string ...$adminClasses): ContainerBuilder
    {
        $container = new ContainerBuilder();
        foreach ($adminClasses as $adminClass) {
            $container->setDefinition($adminClass, (new Definition($adminClass))->addTag('sylius_easy_crud_admin'));
        }

        (new RegisterAdminPass())->process($container);

        return $container;
    }
}
