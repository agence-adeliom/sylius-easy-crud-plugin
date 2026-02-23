<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Autocomplete;

use Adeliom\SyliusEasyCrudPlugin\Autocomplete\ResourceChoiceAutocompleter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\UX\Autocomplete\Controller\EntityAutocompleteController;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;

final class ResourceChoiceAutocompleterTest extends TestCase
{
    private ResourceChoiceAutocompleter $autocompleter;
    private EntitySearchUtil $entitySearchUtil;
    private ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        $this->entitySearchUtil = $this->createMock(EntitySearchUtil::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->autocompleter = new ResourceChoiceAutocompleter(
            PropertyAccess::createPropertyAccessor(),
            $this->entitySearchUtil,
            $this->managerRegistry,
        );
    }

    public function testGetEntityClassReturnsConfiguredClass(): void
    {
        $this->setOptions(['class' => 'App\Entity\Product']);

        $this->assertSame('App\Entity\Product', $this->autocompleter->getEntityClass());
    }

    public function testGetEntityClassThrowsWhenNoClass(): void
    {
        $this->setOptions([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->autocompleter->getEntityClass();
    }

    public function testGetLabelWithStringChoiceLabel(): void
    {
        $this->setOptions(['class' => \stdClass::class, 'choice_label' => 'name']);

        $entity = new class {
            public string $name = 'Test Product';
        };

        $this->assertSame('Test Product', $this->autocompleter->getLabel($entity));
    }

    public function testGetLabelWithCallableChoiceLabel(): void
    {
        $this->setOptions([
            'class' => \stdClass::class,
            'choice_label' => fn (object $e): string => 'Custom: ' . $e->name,
        ]);

        $entity = new class {
            public string $name = 'Foo';
        };

        $this->assertSame('Custom: Foo', $this->autocompleter->getLabel($entity));
    }

    public function testGetLabelFallsBackToToString(): void
    {
        $this->setOptions(['class' => \stdClass::class]);

        $entity = new class {
            public function __toString(): string
            {
                return 'Stringified';
            }
        };

        $this->assertSame('Stringified', $this->autocompleter->getLabel($entity));
    }

    public function testGetValueUsesChoiceValue(): void
    {
        $this->setOptions(['class' => \stdClass::class, 'choice_value' => 'code']);

        $entity = new class {
            public string $code = 'ABC';
        };

        $this->assertSame('ABC', $this->autocompleter->getValue($entity));
    }

    public function testGetValueFallsBackToIdentifier(): void
    {
        $this->setOptions(['class' => \stdClass::class]);

        $entity = new class {
            public int $id = 42;
        };

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getIdentifierValues')->willReturn([42]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getClassMetadata')->willReturn($metadata);

        $this->managerRegistry->method('getManagerForClass')->willReturn($manager);

        $this->assertSame(42, $this->autocompleter->getValue($entity));
    }

    public function testCreateFilteredQueryBuilderWithEmptyQuery(): void
    {
        $this->setOptions(['class' => 'App\Entity\Product', 'max_results' => 5]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('setMaxResults')->with(5)->willReturnSelf();

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('createQueryBuilder')->with('entity')->willReturn($qb);

        $this->entitySearchUtil->expects($this->never())->method('addSearchClause');

        $result = $this->autocompleter->createFilteredQueryBuilder($repository, '');
        $this->assertSame($qb, $result);
    }

    public function testCreateFilteredQueryBuilderWithSearchableFields(): void
    {
        $this->setOptions([
            'class' => 'App\Entity\Product',
            'searchable_fields' => ['name', 'code'],
        ]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('setMaxResults')->willReturnSelf();

        $repository = $this->createMock(EntityRepository::class);
        $repository->method('createQueryBuilder')->willReturn($qb);

        $this->entitySearchUtil->expects($this->once())
            ->method('addSearchClause')
            ->with($qb, 'test', 'App\Entity\Product', ['name', 'code']);

        $this->autocompleter->createFilteredQueryBuilder($repository, 'test');
    }

    public function testIsGrantedAlwaysReturnsTrue(): void
    {
        $security = $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class);
        $this->assertTrue($this->autocompleter->isGranted($security));
    }

    private function setOptions(array $options): void
    {
        $this->autocompleter->setOptions([
            EntityAutocompleteController::EXTRA_OPTIONS => $options,
        ]);
    }
}
