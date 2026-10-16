<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Integration\YamlMode;

use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Test;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;
use Tests\Adeliom\SyliusEasyCrudPlugin\LegacyAdmin\LegacyPostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Repository\PostRepository;

/**
 * The default mode (`sylius_easy_crud.attributes.enabled: false`): the Post resource is declared with the legacy
 * `sylius_resource` + `type: sylius.resource` YAML blocks (tests/TestApplication/config/yaml) and served by an Admin
 * written for easy-crud 2.3, without #[AsAdmin], in a dedicated environment (its own container, a SQLite database).
 */
final class YamlDeclaredResourceTest extends WebTestCase
{
    private const ALIAS = 'tests_adeliom_sylius_easy_crud_plugin.tests_adeliom_sylius_easy_crud_plugin_entity_post';

    private const ROUTE_PREFIX = 'tests_adeliom_sylius_easy_crud_plugin_admin_tests_adeliom_sylius_easy_crud_plugin_entity_post';

    /** @var array<string, mixed> */
    private array $server = [];

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        // read by the Sylius test application kernel when it loads its configuration and its routes
        $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] = '@SyliusEasyCrudPlugin/tests/TestApplication/config/yaml/config.yaml';
        $_SERVER['SYLIUS_TEST_APP_ROUTES_TO_IMPORT'] = '@SyliusEasyCrudPlugin/tests/TestApplication/config/yaml/routes.yaml';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->server;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_yaml'] + $options);
    }

    #[Test]
    public function the_yaml_resource_is_served_by_the_easy_crud_controller_and_the_legacy_admin(): void
    {
        self::bootKernel();

        /** @var array<string, array{classes: array<string, string>}> $resources */
        $resources = self::getContainer()->getParameter('sylius.resources');

        self::assertSame(SyliusCrudResourceController::class, $resources[self::ALIAS]['classes']['controller']);
        self::assertSame(LegacyPostAdmin::class, $resources[self::ALIAS]['classes']['form']);
        self::assertSame(PostRepository::class, $resources[self::ALIAS]['classes']['repository']);
    }

    #[Test]
    public function the_yaml_routes_are_served_under_the_sylius_admin_path_with_the_admin_grid_and_form(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);

        self::assertSame(
            '/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/',
            $router->generate(self::ROUTE_PREFIX . '_index'),
        );

        $index = $router->getRouteCollection()->get(self::ROUTE_PREFIX . '_index');
        $update = $router->getRouteCollection()->get(self::ROUTE_PREFIX . '_update');
        self::assertNotNull($index);
        self::assertNotNull($update);
        self::assertSame('admin_tests_adeliom_sylius_easy_crud_plugin_entity_post', $index->getDefault('_sylius')['grid']);
        self::assertSame(LegacyPostAdmin::class, $update->getDefault('_sylius')['form']['type']);
    }

    #[Test]
    public function no_resource_is_declared_from_the_attributes(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertFalse($container->getParameter('sylius_easy_crud.attributes.enabled'));
        self::assertSame([], $container->getParameter('sylius_easy_crud.attributes.routing'));
        self::assertSame([], $container->getParameter('sylius_easy_crud.attributes.declared_aliases'));
    }

    #[Test]
    public function the_index_page_lists_the_posts_with_the_grid_of_the_legacy_admin(): void
    {
        $client = $this->loggedInAdmin();
        $this->createPost('A legacy post');

        $client->request('GET', '/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'A legacy post');
        self::assertSelectorTextContains('table', 'Legacy post name');
    }

    #[Test]
    public function the_create_page_shows_the_form_of_the_legacy_admin(): void
    {
        $client = $this->loggedInAdmin();

        $client->request('GET', '/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="legacy_post_admin"] input[type="checkbox"][name="legacy_post_admin[enabled]"]');
        self::assertSelectorTextContains('label[for="legacy_post_admin_enabled"]', 'Legacy post enabled');
    }

    #[Test]
    public function the_update_page_shows_the_edited_post_in_the_form_of_the_legacy_admin(): void
    {
        $client = $this->loggedInAdmin();
        $post = $this->createPost('A legacy post');

        $client->request('GET', sprintf('/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/%d/edit', $post->getId()));

        self::assertResponseIsSuccessful();
        // a field without form type, whose type is guessed from the Doctrine mapping by the Admin
        self::assertSelectorExists('form[name="legacy_post_admin"] input[type="text"][value="A legacy post"]');
        self::assertSelectorTextContains('label[for="legacy_post_admin_enabled"]', 'Legacy post enabled');
    }

    /**
     * Sylius 2 builds the index links of the update page (breadcrumb, back button) with `vars.route.parameters`.
     */
    #[Test]
    public function the_index_links_of_the_update_page_do_not_carry_the_edited_id(): void
    {
        $client = $this->loggedInAdmin();
        $post = $this->createPost('A legacy post');

        $crawler = $client->request('GET', sprintf('/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/%d/edit', $post->getId()));

        self::assertResponseIsSuccessful();
        $indexLinks = $crawler->filter('a[href^="/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/?"], a[href="/admin/tests-adeliom-sylius-easy-crud-plugin-entity-posts/"]');
        self::assertGreaterThan(0, $indexLinks->count());
        foreach ($indexLinks as $link) {
            self::assertInstanceOf(\DOMElement::class, $link);
            self::assertStringNotContainsString('id=', $link->getAttribute('href'));
        }
    }

    private function loggedInAdmin(): KernelBrowser
    {
        $client = self::createClient();
        $entityManager = $this->entityManager();

        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        /** @var FactoryInterface<LocaleInterface> $localeFactory */
        $localeFactory = self::getContainer()->get('sylius.factory.locale');
        $locale = $localeFactory->createNew();
        $locale->setCode('en_US');
        $entityManager->persist($locale);

        /** @var FactoryInterface<AdminUserInterface> $adminUserFactory */
        $adminUserFactory = self::getContainer()->get('sylius.factory.admin_user');
        $adminUser = $adminUserFactory->createNew();
        $adminUser->setUsername('admin');
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPlainPassword('admin');
        $adminUser->setLocaleCode('en_US');
        $adminUser->setEnabled(true);
        $entityManager->persist($adminUser);
        $entityManager->flush();

        $client->loginUser($adminUser, 'admin');

        return $client;
    }

    private function createPost(string $name): Post
    {
        $post = new Post();
        $post->setCurrentLocale('en_US');
        $post->setFallbackLocale('en_US');
        $post->setName($name);
        $post->setEnabled(true);
        $post->setProductsAsJson('[]');

        $entityManager = $this->entityManager();
        $entityManager->persist($post);
        $entityManager->flush();
        $entityManager->clear();

        return $post;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
