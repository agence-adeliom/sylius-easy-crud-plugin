<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\CheckboxField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use PHPUnit\Framework\TestCase;

final class CheckboxFieldTest extends TestCase
{
    public function testNewCreatesInstance(): void
    {
        $field = CheckboxField::new('isActive');

        $this->assertInstanceOf(CheckboxField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testPropertyIsSetCorrectly(): void
    {
        $field = CheckboxField::new('enabled');

        $this->assertSame('enabled', $field->getAsDto()->getProperty());
    }

    public function testLabelIsSetCorrectly(): void
    {
        $field = CheckboxField::new('isActive', 'Is Active?');

        $this->assertSame('Is Active?', $field->getAsDto()->getLabel());
    }

    public function testLabelDefaultsToPropertyName(): void
    {
        $field = CheckboxField::new('enabled');

        $this->assertSame('enabled', $field->getAsDto()->getLabel());
    }

    public function testGridTemplatePathIsSet(): void
    {
        $field = CheckboxField::new('enabled');

        $this->assertSame('@SyliusEasyCrudPlugin/field/yesno/grid.html.twig', $field->getAsDto()->getGridTemplatePath());
    }

    public function testShowTemplatePathIsSet(): void
    {
        $field = CheckboxField::new('enabled');

        $this->assertSame('@SyliusEasyCrudPlugin/field/yesno/show.html.twig', $field->getAsDto()->getShowTemplatePath());
    }
}
