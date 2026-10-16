<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\BookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Admin\MagazineAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\HalfMigrated\HalfMigratedBookAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\NoAttribute\FAQAdmin;

/**
 * Admins carrying #[AsAdmin] while `sylius_easy_crud.attributes.enabled` is false (the default): the attribute does not
 * declare their resource nor their routes, which must still come from the YAML blocks.
 */
final class AdminAttributesDisabledTest extends TestCase
{
    #[Test]
    public function admins_carrying_the_attribute_while_the_discovery_is_disabled_are_reported_with_their_grid_name(): void
    {
        $deprecations = $this->deprecationsWhileCompiling(false, BookAdmin::class, MagazineAdmin::class, FAQAdmin::class);

        self::assertCount(1, $deprecations);
        self::assertStringContainsString('"sylius_easy_crud.attributes.enabled" is false', $deprecations[0]);
        self::assertStringContainsString(sprintf('"%s" (grid "app_admin_book")', BookAdmin::class), $deprecations[0]);
        self::assertStringContainsString(sprintf('"%s" (grid "%s")', MagazineAdmin::class, MagazineAdmin::getName()), $deprecations[0]);
        self::assertStringNotContainsString(FAQAdmin::class, $deprecations[0]);
    }

    #[Test]
    public function nothing_is_reported_when_the_discovery_is_enabled(): void
    {
        self::assertSame([], $this->deprecationsWhileCompiling(true, BookAdmin::class));
    }

    #[Test]
    public function an_admin_whose_grid_name_cannot_be_derived_while_the_discovery_is_disabled_fails_with_an_explicit_message(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/HalfMigratedBookAdmin.*#\[AsAdmin\].*"sylius_easy_crud\.attributes\.enabled" is false.*grid name.*#\[AsAdmin\(grid:\)\]/s');

        $this->deprecationsWhileCompiling(false, HalfMigratedBookAdmin::class);
    }

    /**
     * @param class-string ...$adminClasses
     *
     * @return list<string>
     */
    private function deprecationsWhileCompiling(bool $attributesEnabled, string ...$adminClasses): array
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius_easy_crud.attributes.enabled', $attributesEnabled);
        foreach ($adminClasses as $adminClass) {
            $container->setDefinition($adminClass, (new Definition($adminClass))->addTag('sylius_easy_crud_admin'));
        }

        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new RegisterAdminPass())->process($container);
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
