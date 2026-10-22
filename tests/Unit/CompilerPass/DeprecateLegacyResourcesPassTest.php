<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\DeprecateLegacyResourcesPass;
use Adeliom\SyliusEasyCrudPlugin\Controller\SyliusCrudResourceController;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Adeliom\SyliusEasyCrudPlugin\Admin\PostAdmin;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\PostTranslation;

final class DeprecateLegacyResourcesPassTest extends TestCase
{
    #[Test]
    public function an_easy_crud_resource_declared_in_yaml_is_deprecated(): void
    {
        $deprecations = $this->deprecationsFor([
            'app.post' => ['classes' => ['model' => Post::class, 'controller' => SyliusCrudResourceController::class]],
            'app.page' => ['classes' => ['model' => Post::class, 'form' => PostAdmin::class]],
        ]);

        self::assertCount(2, $deprecations);
        self::assertStringContainsString('"app.post"', $deprecations[0]);
        self::assertStringContainsString('"app.page"', $deprecations[1]);
    }

    #[Test]
    public function the_deprecation_tells_how_to_migrate(): void
    {
        $deprecations = $this->deprecationsFor([
            'app.post' => ['classes' => ['model' => Post::class, 'controller' => SyliusCrudResourceController::class]],
        ]);

        self::assertSame(
            'Since agence-adeliom/sylius-easy-crud-plugin 2.3.2: Declaring the easy-crud resource "app.post" through legacy YAML ' .
            '(sylius_resource + "type: sylius.resource") is deprecated and will be removed in 3.0. Set ' .
            '"sylius_easy_crud.attributes.enabled: true" and declare it with #[AsAdmin] on its Admin class instead: see ' .
            '"How to remove the deprecations" in vendor/agence-adeliom/sylius-easy-crud-plugin/UPGRADE.md.',
            $deprecations[0],
        );
    }

    #[Test]
    public function a_resource_declared_with_an_attribute_is_not_deprecated(): void
    {
        $deprecations = $this->deprecationsFor(
            ['app.post' => ['classes' => ['model' => Post::class, 'controller' => SyliusCrudResourceController::class]]],
            ['app.post'],
        );

        self::assertSame([], $deprecations);
    }

    #[Test]
    public function translation_sub_resources_and_other_resources_are_not_deprecated(): void
    {
        $deprecations = $this->deprecationsFor([
            'app.post_translation' => ['classes' => ['model' => PostTranslation::class, 'controller' => SyliusCrudResourceController::class]],
            'app.product_tag' => ['classes' => ['model' => Post::class, 'controller' => ResourceController::class]],
        ]);

        self::assertSame([], $deprecations);
    }

    /**
     * A plugin (e.g. happy-cms) may still declare its own resources in YAML: the application cannot migrate them.
     */
    #[Test]
    public function a_resource_whose_model_and_admin_come_from_the_vendor_directory_is_not_deprecated(): void
    {
        $deprecations = $this->deprecationsFor([
            'vendor.product' => ['classes' => ['model' => Product::class, 'controller' => SyliusCrudResourceController::class]],
        ]);

        self::assertSame([], $deprecations);
    }

    #[Test]
    public function a_vendor_admin_on_a_model_of_the_application_is_deprecated(): void
    {
        $deprecations = $this->deprecationsFor([
            'app.product' => ['classes' => ['model' => Post::class, 'form' => ProductType::class, 'controller' => SyliusCrudResourceController::class]],
        ]);

        self::assertCount(1, $deprecations);
    }

    /**
     * A plugin installed from a Composer path repository is a symlink in vendor/ to a directory outside the project
     * (e.g. /var/www/plugins/the-plugin): PHP reports the real path of its classes, outside vendor/.
     */
    #[Test]
    public function a_resource_whose_classes_live_outside_the_project_directory_is_not_deprecated(): void
    {
        $projectDirectory = sys_get_temp_dir() . '/easy-crud-project-' . bin2hex(random_bytes(4));
        mkdir($projectDirectory);

        try {
            $deprecations = $this->deprecationsFor(
                ['app.post' => ['classes' => ['model' => Post::class, 'form' => PostAdmin::class]]],
                projectDirectory: $projectDirectory,
            );
        } finally {
            rmdir($projectDirectory);
        }

        self::assertSame([], $deprecations);
    }

    #[Test]
    public function a_resource_whose_classes_live_in_the_project_directory_reached_through_a_symlink_is_deprecated(): void
    {
        $projectDirectory = sys_get_temp_dir() . '/easy-crud-project-link-' . bin2hex(random_bytes(4));
        symlink(\dirname(__DIR__, 3), $projectDirectory);

        try {
            $deprecations = $this->deprecationsFor(
                ['app.post' => ['classes' => ['model' => Post::class, 'form' => PostAdmin::class]]],
                projectDirectory: $projectDirectory,
            );
        } finally {
            unlink($projectDirectory);
        }

        self::assertCount(1, $deprecations);
    }

    /**
     * @param array<string, mixed> $resources
     * @param list<string>         $declaredAliases
     *
     * @return list<string>
     */
    private function deprecationsFor(array $resources, array $declaredAliases = [], ?string $projectDirectory = null): array
    {
        $container = new ContainerBuilder();
        if (null !== $projectDirectory) {
            $container->setParameter('kernel.project_dir', $projectDirectory);
        }
        $container->setParameter('sylius.resources', $resources);
        $container->setParameter('sylius_easy_crud.attributes.declared_aliases', $declaredAliases);

        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            (new DeprecateLegacyResourcesPass())->process($container);
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
