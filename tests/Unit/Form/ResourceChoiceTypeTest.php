<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Form;

use Adeliom\SyliusEasyCrudPlugin\Form\ResourceChoiceType;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactoryInterface;

final class ResourceChoiceTypeTest extends TestCase
{
    /** @var RepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private RepositoryInterface $repository;

    private DataTransformerInterface $transformer;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RepositoryInterface::class);

        $builder = new FormBuilder('field', null, new EventDispatcher(), $this->createMock(FormFactoryInterface::class));
        (new ResourceChoiceType($this->createMock(ServiceRegistryInterface::class)))->buildForm($builder, [
            'repository' => $this->repository,
            'choice_value' => 'id',
            'persist_into_an_array' => true,
            'multiple' => true,
        ]);
        $this->transformer = $builder->getModelTransformers()[0];
    }

    /** @return iterable<string, array{mixed}> */
    public static function emptyValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'null' => [null];
        yield 'empty array' => [[]];
        yield 'blank items' => [['', ' ']];
        yield 'only separators' => [',,'];
    }

    /** @dataProvider emptyValues */
    public function testAnEmptyStoredValueIsNeverLookedUp(mixed $value): void
    {
        $this->repository->expects(self::never())->method('findOneBy');

        $collection = $this->transformer->transform($value);

        self::assertInstanceOf(Collection::class, $collection);
        self::assertCount(0, $collection);
    }

    public function testAResourceDeletedSinceTheLastSaveIsDropped(): void
    {
        $kept = new \stdClass();
        $this->repository->method('findOneBy')->willReturnCallback(static fn (array $c): ?object => '2' === $c['id'] ? $kept : null);

        $collection = $this->transformer->transform('1,2');

        self::assertSame([$kept], $collection->toArray());
    }
}
