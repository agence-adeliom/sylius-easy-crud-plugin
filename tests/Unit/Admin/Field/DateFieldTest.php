<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateTimeField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\DateType;

final class DateFieldTest extends TestCase
{
    public function testNewCreatesInstance(): void
    {
        $field = DateField::new('createdAt');

        $this->assertInstanceOf(DateField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testPropertyIsSetCorrectly(): void
    {
        $field = DateField::new('publishedAt');

        $this->assertSame('publishedAt', $field->getAsDto()->getProperty());
    }

    public function testLabelIsSetCorrectly(): void
    {
        $field = DateField::new('createdAt', 'Created At');

        $this->assertSame('Created At', $field->getAsDto()->getLabel());
    }

    public function testFormTypeIsSetToDateType(): void
    {
        $field = DateField::new('createdAt');

        $this->assertSame(DateType::class, $field->getAsDto()->getFormType());
    }

    public function testGridTemplatePathIsSet(): void
    {
        $field = DateField::new('createdAt');

        $this->assertSame('@SyliusEasyCrudPlugin/field/date/grid.html.twig', $field->getAsDto()->getGridTemplatePath());
    }

    public function testShowTemplatePathIsSet(): void
    {
        $field = DateField::new('createdAt');

        $this->assertSame('@SyliusEasyCrudPlugin/field/date/show.html.twig', $field->getAsDto()->getShowTemplatePath());
    }

    public function testSetTimezoneWithValidTimezone(): void
    {
        $field = DateField::new('createdAt')->setTimezone('Europe/Paris');

        $this->assertSame('Europe/Paris', $field->getAsDto()->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testSetTimezoneThrowsExceptionForInvalidTimezone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not a valid PHP timezone ID/');

        DateField::new('createdAt')->setTimezone('Invalid/Timezone');
    }

    public function testSetFormatWithValidPattern(): void
    {
        $field = DateField::new('createdAt')->setFormat('dd/MM/yyyy');

        $this->assertSame('dd/MM/yyyy', $field->getAsDto()->getCustomOption(DateField::OPTION_DATE_PATTERN));
    }

    public function testSetFormatThrowsExceptionForEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DateField::new('createdAt')->setFormat('');
    }

    public function testSetFormatThrowsExceptionForNone(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        DateField::new('createdAt')->setFormat(DateTimeField::FORMAT_NONE);
    }

    public function testRenderAsNativeWidget(): void
    {
        $field = DateField::new('createdAt')->renderAsNativeWidget();

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $field->getAsDto()->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testRenderAsChoice(): void
    {
        $field = DateField::new('createdAt')->renderAsChoice();

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $field->getAsDto()->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testRenderAsText(): void
    {
        $field = DateField::new('createdAt')->renderAsText();

        $this->assertSame(DateTimeField::WIDGET_TEXT, $field->getAsDto()->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testRenderAsNativeWidgetWithFalse(): void
    {
        $field = DateField::new('createdAt')->renderAsChoice()->renderAsNativeWidget(false);

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $field->getAsDto()->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testIsImmutable(): void
    {
        $field = DateField::new('createdAt')->isImmutable();

        $this->assertTrue($field->getAsDto()->getCustomOption(DateField::IMMUTABLE));
    }
}
