<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Integration\Mate;

use Adeliom\SyliusEasyCrudPlugin\Mate\Tool\InspectAdmin;
use Adeliom\SyliusEasyCrudPlugin\Mate\Tool\ListAdmins;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Sylius\MateExtension\Kernel\HostContainerProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusEasyCrudPlugin\Admin\PostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

final class MateToolsTest extends KernelTestCase
{
    private const POST_ALIAS = 'tests_adeliom_sylius_easy_crud_plugin.tests_adeliom_sylius_easy_crud_plugin_entity_post';

    #[Test]
    public function it_lists_the_easy_crud_admins_with_their_declaration_and_routes(): void
    {
        $result = (new ListAdmins($this->host()))();

        $items = $result['items'] ?? null;
        self::assertIsArray($items);
        $post = $this->findByAlias($items, self::POST_ALIAS);

        self::assertSame(PostAdmin::class, $post['admin']);
        self::assertSame(Post::class, $post['model']);
        self::assertSame('attribute', $post['declaration']);
        self::assertSame('admin_tests_adeliom_sylius_easy_crud_plugin_entity_post', $post['grid']);
        self::assertIsArray($post['routes']);
        self::assertContains('index', array_column($post['routes'], 'action'));
    }

    #[Test]
    public function it_leaves_out_the_translation_sub_resources_sharing_the_admin_of_their_parent(): void
    {
        $result = (new ListAdmins($this->host()))();

        $items = $result['items'] ?? null;
        self::assertIsArray($items);
        self::assertNotContains(self::POST_ALIAS . '_translation', array_column($items, 'alias'));
    }

    #[Test]
    public function it_filters_the_admins_by_alias_prefix(): void
    {
        $result = (new ListAdmins($this->host()))('unknown_prefix.');

        self::assertSame([], $result['items'] ?? null);
    }

    #[Test]
    public function it_inspects_the_fields_filters_and_built_form_of_an_admin(): void
    {
        $result = (new InspectAdmin($this->host()))(self::POST_ALIAS);

        $items = $result['items'] ?? null;
        self::assertIsArray($items);
        $post = $items[0];
        self::assertIsArray($post);

        self::assertSame(PostAdmin::class, $post['admin']);
        self::assertSame(['alias' => self::POST_ALIAS, 'grid' => 'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post'], array_intersect_key(
            $post['attribute'],
            ['alias' => true, 'grid' => true],
        ));

        $pages = $post['pages'];
        self::assertIsArray($pages);
        self::assertContains('id', array_column($pages['index']['fields'], 'property'));
        self::assertNotContains('icon', array_column($pages['index']['fields'], 'property'));
        self::assertContains('icon', array_column($pages['update']['fields'], 'property'));
        // The built form needs the database (choices of the relations), absent from this suite: only its presence on
        // the form pages is checked here.
        self::assertArrayNotHasKey('form', $pages['index']);
        self::assertArrayHasKey('form', $pages['update']);

        self::assertSame([['name' => 'enabled', 'type' => 'boolean']], $post['filters']);
    }

    #[Test]
    public function it_restricts_the_inspection_to_one_page(): void
    {
        $result = (new InspectAdmin($this->host()))(self::POST_ALIAS, 'index');

        $items = $result['items'] ?? null;
        self::assertIsArray($items);
        self::assertIsArray($items[0]);
        self::assertSame(['index'], array_keys($items[0]['pages']));
    }

    #[Test]
    public function it_reports_an_unknown_alias(): void
    {
        $result = (new InspectAdmin($this->host()))('app.unknown');

        self::assertSame([], $result['items'] ?? null);
        self::assertIsString($result['note'] ?? null);
    }

    private function host(): HostContainerProvider
    {
        self::bootKernel();
        $container = self::getContainer();

        return new class($container) implements HostContainerProvider {
            public function __construct(
                private readonly ContainerInterface $container,
            ) {
            }

            public function getContainer(): ContainerInterface
            {
                return $this->container;
            }

            public function getRootDir(): string
            {
                return '';
            }
        };
    }

    /**
     * @param array<mixed> $items
     *
     * @return array<mixed>
     */
    private function findByAlias(array $items, string $alias): array
    {
        foreach ($items as $item) {
            if (\is_array($item) && ($item['alias'] ?? null) === $alias) {
                return $item;
            }
        }

        self::fail(sprintf('The admin "%s" is not listed.', $alias));
    }
}
