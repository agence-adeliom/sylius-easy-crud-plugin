<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Integration\PositionSort;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\Test;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\PositionSort\SortableItem;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\PositionSort\TreeItem;
use Tests\Adeliom\SyliusEasyCrudPlugin\Security\FixedCsrfTokenManager;

/**
 * The drag and drop of the PositionSortableField column, in a dedicated environment (its own container, a SQLite
 * database, a CSRF token manager without session).
 */
final class PositionSortControllerTest extends WebTestCase
{
    /** @var array<string, mixed> */
    private array $server = [];

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        // read by the Sylius test application kernel when it loads its configuration and its routes
        $_SERVER['SYLIUS_TEST_APP_CONFIGS_TO_IMPORT'] = '@SyliusEasyCrudPlugin/tests/TestApplication/config/position_sort/config.yaml';
        $_SERVER['SYLIUS_TEST_APP_ROUTES_TO_IMPORT'] = '@SyliusEasyCrudPlugin/tests/TestApplication/config/position_sort/routes.yaml';

        $this->client = $this->loggedInAdmin();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->server;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_position_sort'] + $options);
    }

    #[Test]
    public function moving_up_shifts_down_only_the_rows_between_the_new_and_the_old_position(): void
    {
        $items = $this->createSortableItems(7);

        // the row at position 4 dropped between the rows at positions 1 and 2
        $this->sort('sortable_item', $items[4], $items[1], $items[2]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 1, 3, 4, 2, 5, 6], $this->positions($items));
    }

    #[Test]
    public function moving_down_shifts_up_only_the_rows_between_the_old_and_the_new_position(): void
    {
        $items = $this->createSortableItems(7);

        // the row at position 2 dropped between the rows at positions 4 and 5
        $this->sort('sortable_item', $items[2], $items[4], $items[5]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 1, 4, 2, 3, 5, 6], $this->positions($items));
    }

    /**
     * The new position is the position of a neighbour, not an index in the page: on the second page of a list
     * paginated by 3, the first row of the page (position 3) dropped after the second one (position 4).
     */
    #[Test]
    public function the_new_position_does_not_depend_on_the_page(): void
    {
        $items = $this->createSortableItems(7);

        $this->sort('sortable_item', $items[3], $items[4], $items[5]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 1, 2, 4, 3, 5, 6], $this->positions($items));
    }

    #[Test]
    public function a_row_dropped_at_the_same_place_keeps_every_position(): void
    {
        $items = $this->createSortableItems(4);

        $this->sort('sortable_item', $items[2], $items[1], $items[3]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 1, 2, 3], $this->positions($items));
    }

    #[Test]
    public function a_row_without_position_is_inserted_before_its_next_row(): void
    {
        $items = $this->createSortableItems(4);
        $items[3]->setPosition(null);
        $this->entityManager()->flush();

        $this->sort('sortable_item', $items[3], $items[0], $items[1]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 2, 3, 1], $this->positions($items));
    }

    /**
     * Rows with the same position are listed by id: the list is renumbered in that order before the move, otherwise the
     * new order could not be expressed with the positions of the neighbours.
     */
    #[Test]
    public function rows_with_the_same_position_are_renumbered_before_the_move(): void
    {
        $items = $this->createSortableItems(4);
        foreach ([0, 4, 4, 5] as $index => $position) {
            $items[$index]->setPosition($position);
        }
        $this->entityManager()->flush();

        // the first row at position 4 dropped after the second one
        $this->sort('sortable_item', $items[1], $items[2], $items[3]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 2, 1, 3], $this->positions($items));
    }

    #[Test]
    public function rows_without_position_are_renumbered_before_the_move(): void
    {
        $items = $this->createSortableItems(3);
        $items[0]->setPosition(null);
        $items[1]->setPosition(null);
        $this->entityManager()->flush();

        // the two rows without position are listed first: the last row dropped between them
        $this->sort('sortable_item', $items[2], $items[0], $items[1]);

        self::assertResponseIsSuccessful();
        self::assertSame([0, 2, 1], $this->positions($items));
    }

    #[Test]
    public function a_child_moves_among_its_siblings(): void
    {
        [$root, $first, $second, $third] = $this->createTree();

        // the third child dropped between the first and the second one
        $this->sort('tree_item', $third, $first, $second);

        self::assertResponseIsSuccessful();
        self::assertSame(['first', 'third', 'second'], $this->childrenNames($root));
    }

    #[Test]
    public function a_child_moves_first_before_its_next_sibling(): void
    {
        [$root, $first, $second, $third] = $this->createTree();

        $this->sort('tree_item', $third, null, $first);

        self::assertResponseIsSuccessful();
        self::assertSame(['third', 'first', 'second'], $this->childrenNames($root));
    }

    /**
     * Bounds broken outside the tree listener (a node with a left value outside its parent): rebuilt before the move.
     */
    #[Test]
    public function an_inconsistent_tree_is_recovered_before_the_move(): void
    {
        [$root, $first, $second, $third] = $this->createTree();
        $this->entityManager()->createQuery(sprintf('UPDATE %s node SET node.rgt = 1000 WHERE node.id = :id', TreeItem::class))
            ->setParameter('id', $second->getId())
            ->execute()
        ;

        $this->sort('tree_item', $third, null, $first);

        self::assertResponseIsSuccessful();
        self::assertSame(['third', 'first', 'second'], $this->childrenNames($root));
        $repository = $this->entityManager()->getRepository(TreeItem::class);
        \assert($repository instanceof \Gedmo\Tree\Entity\Repository\NestedTreeRepository);
        self::assertTrue($repository->verify());
    }

    #[Test]
    public function a_node_cannot_be_moved_next_to_a_node_of_another_parent(): void
    {
        [$root, $first, $second] = $this->createTree();
        $grandChild = new TreeItem('grand child', $first);
        $this->entityManager()->persist($grandChild);
        $this->entityManager()->flush();

        $this->sort('tree_item', $second, $grandChild, null);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['first', 'second', 'third'], $this->childrenNames($root));
    }

    #[Test]
    public function a_root_node_cannot_be_moved(): void
    {
        [$root] = $this->createTree();
        $otherRoot = new TreeItem('other root');
        $this->entityManager()->persist($otherRoot);
        $this->entityManager()->flush();

        $this->sort('tree_item', $otherRoot, $root, null);

        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function a_flat_list_needs_a_resource_sortable_by_position(): void
    {
        [, $first, $second] = $this->createTree();

        $this->client->jsonRequest('POST', '/admin/position-sort/tests_position_sort.tree_item', [
            'id' => $second->getId(),
            'previousId' => null,
            'nextId' => $first->getId(),
            '_token' => FixedCsrfTokenManager::VALID_TOKEN,
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function an_invalid_csrf_token_is_refused(): void
    {
        $items = $this->createSortableItems(3);

        $this->client->jsonRequest('POST', '/admin/position-sort/tests_position_sort.sortable_item', [
            'id' => $items[2]->getId(),
            'previousId' => null,
            'nextId' => $items[0]->getId(),
            '_token' => 'invalid',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([0, 1, 2], $this->positions($items));
    }

    #[Test]
    public function an_unknown_resource_is_not_found(): void
    {
        $this->client->jsonRequest('POST', '/admin/position-sort/tests_position_sort.unknown', [
            'id' => 1,
            '_token' => FixedCsrfTokenManager::VALID_TOKEN,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function the_routes_are_behind_the_admin_firewall(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();

        $client->jsonRequest('POST', '/admin/position-sort/tests_position_sort.sortable_item', ['id' => 1]);

        self::assertResponseRedirects();
        self::assertStringContainsString('/admin/login', (string) $client->getResponse()->headers->get('Location'));
    }

    private function sort(string $resource, object $moved, ?object $previous, ?object $next): void
    {
        $this->client->jsonRequest(
            'POST',
            sprintf('/admin/position-sort/tests_position_sort.%s%s', $resource, 'tree_item' === $resource ? '/tree' : ''),
            [
                'id' => $moved->getId(),
                'previousId' => $previous?->getId(),
                'nextId' => $next?->getId(),
                '_token' => FixedCsrfTokenManager::VALID_TOKEN,
            ],
        );
    }

    /**
     * @return list<SortableItem>
     */
    private function createSortableItems(int $count): array
    {
        $items = [];
        foreach (range(0, $count - 1) as $position) {
            $item = new SortableItem();
            $item->setPosition($position);
            $this->entityManager()->persist($item);
            $items[] = $item;
        }
        $this->entityManager()->flush();

        return $items;
    }

    /**
     * @param list<SortableItem> $items
     *
     * @return list<int|null>
     */
    private function positions(array $items): array
    {
        $entityManager = $this->entityManager();
        $entityManager->clear();

        return array_map(
            static fn (SortableItem $item): ?int => $entityManager->find(SortableItem::class, $item->getId())?->getPosition(),
            $items,
        );
    }

    /**
     * @return array{TreeItem, TreeItem, TreeItem, TreeItem}
     */
    private function createTree(): array
    {
        $root = new TreeItem('root');
        $first = new TreeItem('first', $root);
        $second = new TreeItem('second', $root);
        $third = new TreeItem('third', $root);
        foreach ([$root, $first, $second, $third] as $node) {
            $this->entityManager()->persist($node);
            $this->entityManager()->flush();
        }

        return [$root, $first, $second, $third];
    }

    /**
     * @return list<string>
     */
    private function childrenNames(TreeItem $root): array
    {
        $entityManager = $this->entityManager();
        $entityManager->clear();
        $repository = $entityManager->getRepository(TreeItem::class);
        \assert($repository instanceof \Gedmo\Tree\Entity\Repository\NestedTreeRepository);

        return array_map(
            static fn (TreeItem $child): string => $child->getName(),
            $repository->getChildren($entityManager->find(TreeItem::class, $root->getId()), true, 'lft'),
        );
    }

    private function loggedInAdmin(): KernelBrowser
    {
        $client = self::createClient();
        $client->disableReboot();
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

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
