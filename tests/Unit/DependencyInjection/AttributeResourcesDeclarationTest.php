<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\DependencyInjection\SyliusEasyCrudExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\DependencyInjection\SyliusResourceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Metadata\Fixtures\Model\Book;

/**
 * A resource must be declared once: by #[AsAdmin] or by the legacy YAML, never by both, never by two Admins.
 */
final class AttributeResourcesDeclarationTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../Metadata/Fixtures';

    #[Test]
    public function the_attribute_resources_are_contributed_to_the_sylius_resources(): void
    {
        $container = $this->container(self::FIXTURES . '/ConsistentAdmin');

        (new SyliusEasyCrudExtension())->prepend($container);

        $resources = $container->getExtensionConfig('sylius_resource')[0]['resources'];
        self::assertSame(Book::class, $resources['app.book']['classes']['model']);
        self::assertSame(['app.book'], $container->getParameter('sylius_easy_crud.attributes.declared_aliases'));
    }

    #[Test]
    public function an_alias_declared_by_an_attribute_and_by_a_legacy_yaml_block_fails_the_compilation(): void
    {
        $container = $this->container(self::FIXTURES . '/ConsistentAdmin');
        $container->loadFromExtension('sylius_resource', [
            'resources' => ['app.book' => ['classes' => ['model' => Book::class]]],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/"app\.book".*ConsistentAdmin.*sylius_resource\.resources\.app\.book/s');

        (new SyliusEasyCrudExtension())->prepend($container);
    }

    #[Test]
    public function an_alias_declared_by_two_attributes_fails_the_compilation(): void
    {
        $container = $this->container(self::FIXTURES . '/DuplicateAlias');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/"app\.book".*FirstBookAdmin.*SecondBookAdmin/s');

        (new SyliusEasyCrudExtension())->prepend($container);
    }

    private function container(string $path): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SyliusResourceExtension());
        $container->registerExtension(new SyliusEasyCrudExtension());
        $container->loadFromExtension('sylius_easy_crud', ['attributes' => ['enabled' => true, 'paths' => [$path]]]);

        return $container;
    }
}
