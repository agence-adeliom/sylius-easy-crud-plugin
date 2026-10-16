<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\DependencyInjection;

use Adeliom\SyliusEasyCrudPlugin\DependencyInjection\SyliusEasyCrudExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The plugin must not change how the host application autowires classes it does not own.
 */
final class HostApplicationServicesTest extends TestCase
{
    #[Test]
    #[DataProvider('foreignClasses')]
    public function the_plugin_registers_no_autowiring_alias_for_a_foreign_class(string $class): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius.grid.templates.action', []);
        $container->setParameter('sylius.grid.templates.bulk_action', []);

        (new SyliusEasyCrudExtension())->load([], $container);

        self::assertFalse($container->has($class), sprintf('"%s" should not be registered by the plugin.', $class));
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function foreignClasses(): iterable
    {
        yield 'property accessor' => [PropertyAccessor::class];
        yield 'doctrine type guesser' => [DoctrineOrmTypeGuesser::class];
    }
}
