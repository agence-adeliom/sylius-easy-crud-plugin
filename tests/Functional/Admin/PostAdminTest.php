<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Functional\Admin;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusEasyCrudPlugin\Admin\PostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

final class PostAdminTest extends KernelTestCase
{
    private PostAdmin $postAdmin;

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);

        $container = static::getContainer();

        // Récupère le service PostAdmin depuis le conteneur
        $this->postAdmin = $container->get('test.service_container')
            ->get(PostAdmin::class);
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(Post::class, PostAdmin::getEntityFqcn());
    }

    public function testGetName(): void
    {
        $this->assertSame(
            'admin_tests_adeliom_sylius_easy_crud_plugin_entity_post',
            PostAdmin::getName()
        );
    }

    public function testGetDefaultSortColumn(): void
    {
        $this->assertSame('', PostAdmin::getDefaultSortColumn());
    }

    public function testGetDefaultSortOrder(): void
    {
        $this->assertSame('asc', PostAdmin::getDefaultSortOrder());
    }

    public function testGetLimits(): void
    {
        $this->assertSame([10, 25, 50], PostAdmin::getLimits());
    }

    public function testConfigureRepository(): void
    {
        $repositoryClass = $this->postAdmin->configureRepository();

        $this->assertNotEmpty($repositoryClass);
        $this->assertTrue(class_exists($repositoryClass));
    }

    public function testConfigureFields(): void
    {
        $fields = iterator_to_array($this->postAdmin->configureFields('index'));

        $this->assertNotEmpty($fields);
        $this->assertContainsOnlyInstancesOf(
            \Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface::class,
            $fields
        );
    }

    public function testConfigureFilters(): void
    {
        $filters = iterator_to_array($this->postAdmin->configureFilters());

        $this->assertNotEmpty($filters);
        $this->assertCount(1, $filters);
    }

    public function testConfigureActions(): void
    {
        $actions = $this->postAdmin->configureActions('index');

        $this->assertInstanceOf(
            \Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Actions::class,
            $actions
        );
    }

    public function testToArrayReturnsGridConfiguration(): void
    {
        $gridConfig = $this->postAdmin->toArray();

        $this->assertIsArray($gridConfig);
        $this->assertArrayHasKey('driver', $gridConfig);
        $this->assertArrayHasKey('resource', $gridConfig);
    }
}
