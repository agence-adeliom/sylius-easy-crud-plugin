<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\PositionSortableField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use PHPUnit\Framework\TestCase;

final class PositionSortableFieldTest extends TestCase
{
    public function testFlatListIsTheDefault(): void
    {
        $dto = PositionSortableField::new()->getAsDto();

        $this->assertSame('position', $dto->getProperty());
        $this->assertFalse($dto->getCustomOption(PositionSortableField::OPTION_TREE));
        $this->assertNull($dto->getCustomOption(PositionSortableField::OPTION_PARENT_PROPERTY));
    }

    public function testListIsSortedByThePositionProperty(): void
    {
        $dto = PositionSortableField::new('rank', 'Rank')->getAsDto();

        $this->assertSame('Rank', $dto->getLabel());
        $this->assertSame('rank', $dto->getSortablePath());
        $this->assertSame('@SyliusEasyCrudPlugin/field/positionsortable/grid.html.twig', $dto->getGridTemplatePath());
    }

    public function testShownOnTheIndexPageOnly(): void
    {
        $displayedOn = PositionSortableField::new()->getAsDto()->getDisplayedOn();

        $this->assertTrue($displayedOn->has(Crud::PAGE_INDEX));
        $this->assertFalse($displayedOn->has(Crud::PAGE_NEW));
        $this->assertFalse($displayedOn->has(Crud::PAGE_EDIT));
        $this->assertFalse($displayedOn->has(Crud::PAGE_DETAIL));
    }

    public function testTreeIsNotSortableByItsHeader(): void
    {
        $dto = PositionSortableField::new()->asTree('parentItem')->getAsDto();

        $this->assertTrue($dto->getCustomOption(PositionSortableField::OPTION_TREE));
        $this->assertSame('parentItem', $dto->getCustomOption(PositionSortableField::OPTION_PARENT_PROPERTY));
        $this->assertFalse($dto->isSortable());
    }

    public function testGridFieldRendersTheWholeRow(): void
    {
        $field = PositionSortableField::create('position');

        $this->assertSame('.', $field->getPath());
        $this->assertSame('@SyliusEasyCrudPlugin/field/positionsortable/grid.html.twig', $field->getOptions()['template']);
    }
}
