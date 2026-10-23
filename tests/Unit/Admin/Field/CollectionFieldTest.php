<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CollectionField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator\SortableCollectionConfigurator;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SortableCollectionField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Form\SortableCollectionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * A collection is a sortable collection without the drag and drop: the same form type, the same accordion entries,
 * the same options, the submitted array re-indexed as a list.
 */
final class CollectionFieldTest extends TestCase
{
    public function testNewCreatesInstance(): void
    {
        $field = CollectionField::new('emails', 'E-mails');

        $this->assertInstanceOf(FieldInterface::class, $field);
        $this->assertSame('emails', $field->getAsDto()->getProperty());
        $this->assertSame('E-mails', $field->getAsDto()->getLabel());
    }

    public function testItIsASortableCollectionWithoutDrag(): void
    {
        $dto = $this->configured(CollectionField::new('emails')->setEntryType(EmailType::class));

        $this->assertSame(SortableCollectionType::class, $dto->getFormType());
        $this->assertFalse($dto->getFormTypeOption('allow_drag'));
        $this->assertTrue($dto->getFormTypeOption('allow_add'));
        $this->assertTrue($dto->getFormTypeOption('allow_delete'));
        $this->assertSame(EmailType::class, $dto->getFormTypeOption('entry_type'));
        // re-indexed as a list by default (`keep_as_list` of the form type)
        $this->assertNull($dto->getFormTypeOption('keep_as_list'));
    }

    public function testEntryTitleIsAFormTypeOption(): void
    {
        $entryTitle = static fn (mixed $entry): string => 'title';
        $dto = CollectionField::new('emails')->setEntryTitle($entryTitle)->getAsDto();

        $this->assertSame($entryTitle, $dto->getFormTypeOption('entry_title'));
    }

    public function testKeysCanBeKept(): void
    {
        $dto = CollectionField::new('emails')->setFormTypeOption('keep_as_list', false)->getAsDto();

        $this->assertFalse($dto->getFormTypeOption('keep_as_list'));
    }

    public function testAddAndDeleteCanBeDisabled(): void
    {
        $dto = $this->configured(CollectionField::new('emails')->allowAdd(false)->allowDelete(false));

        $this->assertFalse($dto->getFormTypeOption('allow_add'));
        $this->assertFalse($dto->getFormTypeOption('allow_delete'));
    }

    public function testSortableCollectionKeepsItsDrag(): void
    {
        $dto = $this->configured(SortableCollectionField::new('data'));

        $this->assertTrue($dto->getFormTypeOption('allow_drag'));
    }

    public function testTemplatesAreTheSortableCollectionOnes(): void
    {
        $dto = CollectionField::new('emails')->getAsDto();

        $this->assertSame('@SyliusEasyCrudPlugin/field/sortablecollection/grid.html.twig', $dto->getGridTemplatePath());
        $this->assertSame('@SyliusEasyCrudPlugin/field/sortablecollection/show.html.twig', $dto->getShowTemplatePath());
        $this->assertContains('@SyliusEasyCrudPlugin/field/sortablecollection/form.html.twig', $dto->getFormThemes());
    }

    private function configured(FieldInterface $field): FieldDto
    {
        $dto = $field->getAsDto();
        // set by the field collection of the CRUD
        $dto->setFieldFqcn($field::class);
        $configurator = new SortableCollectionConfigurator();
        $this->assertTrue($configurator->supports($dto));
        $configurator->configure($dto);

        return $dto;
    }
}
