<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\DependencyInjection\SyliusEasyCrudExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\DependencyInjection\SyliusResourceExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * `sylius_easy_crud.attributes` is read when the container is built (prepend), before the configuration is
 * processed: its values must be known at that time.
 */
final class AttributesConfigurationTest extends TestCase
{
    private const ADMINS = __DIR__ . '/../Metadata/Fixtures/ConsistentAdmin';

    #[Test]
    public function enabled_can_be_read_from_a_parameter(): void
    {
        $container = $this->container(['enabled' => '%easy_crud_attributes%', 'paths' => [self::ADMINS]]);
        $container->setParameter('easy_crud_attributes', true);

        (new SyliusEasyCrudExtension())->prepend($container);

        self::assertSame(['app.book'], $container->getParameter('sylius_easy_crud.attributes.declared_aliases'));
    }

    #[Test]
    public function a_parameter_disabling_the_attributes_disables_them(): void
    {
        $container = $this->container(['enabled' => '%easy_crud_attributes%', 'paths' => [self::ADMINS]]);
        $container->setParameter('easy_crud_attributes', false);

        (new SyliusEasyCrudExtension())->prepend($container);

        self::assertSame([], $container->getParameter('sylius_easy_crud.attributes.declared_aliases'));
    }

    #[Test]
    public function enabled_cannot_be_read_from_an_environment_variable(): void
    {
        $container = $this->container(['enabled' => '%env(bool:EASY_CRUD_ATTRIBUTES)%', 'paths' => [self::ADMINS]]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/sylius_easy_crud\.attributes\.enabled.*environment variable/s');

        (new SyliusEasyCrudExtension())->prepend($container);
    }

    #[Test]
    public function enabled_cannot_be_read_from_a_parameter_holding_an_environment_variable(): void
    {
        $container = $this->container(['enabled' => '%easy_crud_attributes%', 'paths' => [self::ADMINS]]);
        $container->setParameter('easy_crud_attributes', '%env(bool:EASY_CRUD_ATTRIBUTES)%');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/sylius_easy_crud\.attributes\.enabled/');

        (new SyliusEasyCrudExtension())->prepend($container);
    }

    #[Test]
    public function a_missing_directory_fails_the_compilation_when_the_attributes_are_enabled(): void
    {
        $container = $this->container(['enabled' => true, 'paths' => [self::ADMINS, '%kernel.project_dir%/src/Admin']]);
        $container->setParameter('kernel.project_dir', '/does/not/exist');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('#"/does/not/exist/src/Admin".*sylius_easy_crud\.attributes\.paths#s');

        (new SyliusEasyCrudExtension())->prepend($container);
    }

    #[Test]
    public function a_missing_directory_is_ignored_when_the_attributes_are_disabled(): void
    {
        $container = $this->container(['paths' => ['/does/not/exist']]);

        (new SyliusEasyCrudExtension())->prepend($container);

        self::assertSame([], $container->getParameter('sylius_easy_crud.attributes.declared_aliases'));
    }

    /**
     * The Admin classes are discovered when the container is built: adding one to a scanned directory must rebuild the
     * cached container, even when the directory is not covered by the service resources of the application.
     */
    #[Test]
    public function adding_an_admin_to_a_scanned_directory_makes_the_cached_container_stale(): void
    {
        $directory = sys_get_temp_dir() . '/easy-crud-admins-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $file = $directory . '/NewAdmin.php';

        try {
            $container = $this->container(['enabled' => true, 'paths' => [$directory]]);
            (new SyliusEasyCrudExtension())->prepend($container);
            $builtAt = time();

            file_put_contents($file, "<?php\n");
            touch($file, $builtAt + 10);
            touch($directory, $builtAt + 10);

            $fresh = true;
            foreach ($container->getResources() as $resource) {
                if ($resource instanceof SelfCheckingResourceInterface && !$resource->isFresh($builtAt)) {
                    $fresh = false;
                }
            }

            self::assertFalse($fresh);
        } finally {
            @unlink($file);
            rmdir($directory);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function container(array $attributes): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new SyliusResourceExtension());
        $container->registerExtension(new SyliusEasyCrudExtension());
        $container->loadFromExtension('sylius_easy_crud', ['attributes' => $attributes]);

        return $container;
    }
}
