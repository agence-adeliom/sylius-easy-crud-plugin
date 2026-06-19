<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\CompilerPass;

use Adeliom\SyliusEasyCrudPlugin\CompilerPass\RegisterAdminPass;
use Adeliom\SyliusEasyCrudPlugin\Metadata\AsAdmin;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterAdminPassTest extends TestCase
{
    public function testItUsesAttributeResourceClassWhenAdminInheritsEntityFqcnFromConcreteParent(): void
    {
        $container = new ContainerBuilder();
        $definition = (new Definition(ChildAdmin::class))
            ->addTag('sylius_easy_crud_admin');
        $container->setDefinition(ChildAdmin::class, $definition);

        (new RegisterAdminPass())->process($container);

        self::assertSame(AppResource::class, $definition->getArgument(0));
        self::assertSame([], $definition->getArgument(1));
        self::assertTrue($definition->isAutowired());
    }
}

class VendorResource
{
}

class AppResource
{
}

class ParentAdmin
{
    public static function getEntityFqcn(): string
    {
        return VendorResource::class;
    }
}

#[AsAdmin(resourceClass: AppResource::class)]
class ChildAdmin extends ParentAdmin
{
}
