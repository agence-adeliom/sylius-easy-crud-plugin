<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Functional\CrudFactory;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Collection\FieldCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\Field;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Adeliom\SyliusEasyCrudPlugin\Entity\Post;

final class FieldCollectionTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'test']);
    }

    public function testFieldCollectionCanBeCreated(): void
    {
        $container = static::getContainer();
        $configuratorCollection = $container->get('adeliom.sylius.field.configurator.collection');

        $fields = [
            Field::new('id'),
            Field::new('name'),
            CheckboxField::new('enabled'),
            DateField::new('createdAt'),
        ];

        $collection = FieldCollection::new(
            $fields,
            $configuratorCollection,
            null
        );

        $this->assertCount(4, $collection);
    }

    public function testFieldCollectionCanBeIterated(): void
    {
        $container = static::getContainer();
        $configuratorCollection = $container->get('adeliom.sylius.field.configurator.collection');

        $fields = [
            Field::new('id'),
            Field::new('name'),
        ];

        $collection = FieldCollection::new(
            $fields,
            $configuratorCollection,
            null
        );

        $count = 0;
        foreach ($collection as $fieldDto) {
            $count++;
            $this->assertInstanceOf(
                \Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto::class,
                $fieldDto
            );
        }

        $this->assertSame(2, $count);
    }

    public function testFieldCollectionWithResource(): void
    {
        $container = static::getContainer();
        $configuratorCollection = $container->get('adeliom.sylius.field.configurator.collection');

        $post = new Post();
        $post->setName('Test Post');
        $post->setEnabled(true);

        $fields = [
            Field::new('id'),
            Field::new('name'),
            CheckboxField::new('enabled'),
        ];

        $collection = FieldCollection::new(
            $fields,
            $configuratorCollection,
            $post
        );

        $this->assertCount(3, $collection);
    }

    public function testFieldCollectionAppliesConfigurators(): void
    {
        $container = static::getContainer();
        $configuratorCollection = $container->get('adeliom.sylius.field.configurator.collection');

        $fields = [
            DateField::new('createdAt')->setTimezone('Europe/Paris'),
            EnumField::new('status'),
        ];

        $collection = FieldCollection::new(
            $fields,
            $configuratorCollection,
            null
        );

        foreach ($collection as $fieldDto) {
            // Vérifie que les configurateurs ont été appliqués
            $this->assertNotNull($fieldDto->getProperty());
        }

        $this->assertCount(2, $collection);
    }
}
