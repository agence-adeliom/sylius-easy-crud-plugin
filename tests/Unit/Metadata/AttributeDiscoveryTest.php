<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata;

use Adeliom\SyliusEasyCrudPlugin\Metadata\EasyCrudResourceFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\DerivedAlias\NovelAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\NovelResource;

final class AttributeDiscoveryTest extends TestCase
{
    #[Test]
    public function without_default_vars_only_the_vars_set_on_the_attribute_are_kept(): void
    {
        $descriptor = $this->descriptorOf(__DIR__ . '/Fixtures/Admin', 'app.explicit_vars_book');

        self::assertSame([
            'all' => ['icon' => 'tabler:book'],
            'index' => ['header' => 'app.ui.books'],
            'create' => ['header' => 'app.ui.books'],
            'update' => ['header' => 'app.ui.books'],
        ], $descriptor['routing']['config']['vars']);
    }

    #[Test]
    public function the_alias_is_derived_from_the_resource_like_sylius_does_when_none_is_set(): void
    {
        $descriptor = $this->descriptorOf(__DIR__ . '/Fixtures/DerivedAlias', 'app.novel');

        self::assertSame(NovelResource::class, $descriptor['registry']['classes']['model']);
        self::assertSame('app.novel', $descriptor['routing']['config']['alias']);
        self::assertSame('app_admin_novel', $descriptor['routing']['config']['grid']);
        self::assertSame('app_admin_novel', NovelAdmin::getName());
    }

    #[Test]
    public function the_discovery_triggers_no_deprecation(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/DerivedAlias']);
        } finally {
            restore_error_handler();
        }

        self::assertSame([], $deprecations);
    }

    #[Test]
    #[DataProvider('unknownActionDirectories')]
    public function an_unknown_action_in_only_or_except_fails_the_discovery(string $directory, string $argument, string $action): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The easy-crud admin "Tests\\Adeliom\\SyliusEasyCrudPlugin\\Unit\\Metadata\\Fixtures\\%s\\TypoBookAdmin" lists the unknown action "%s" in #[AsAdmin(%s:)]. ' .
            'Valid actions: "show", "index", "create", "update", "delete", "bulkDelete".',
            $directory,
            $action,
            $argument,
        ));

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/' . $directory]);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function unknownActionDirectories(): iterable
    {
        yield 'only' => ['UnknownOnlyAction', 'only', 'indx'];
        yield 'except' => ['UnknownExceptAction', 'except', 'shw'];
    }

    /**
     * The `sylius.resource` route loader refuses them only when the routes are loaded, without naming the Admin.
     */
    #[Test]
    public function only_and_except_set_together_fail_the_compilation(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/OnlyAndExceptBookAdmin.*"only".*"except"/s');

        EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/OnlyAndExcept']);
    }

    #[Test]
    public function abstract_admins_are_not_registered(): void
    {
        self::assertSame([], EasyCrudResourceFactory::build([__DIR__ . '/Fixtures/AbstractAdmin']));
    }

    /**
     * @return array{alias: string, admin: class-string, registry: array<string, mixed>, routing: array{prefix: string, config: array<string, mixed>}}
     */
    private function descriptorOf(string $path, string $alias): array
    {
        foreach (EasyCrudResourceFactory::build([$path]) as $descriptor) {
            if ($alias === $descriptor['alias']) {
                return $descriptor;
            }
        }

        self::fail(sprintf('No descriptor for "%s".', $alias));
    }
}
