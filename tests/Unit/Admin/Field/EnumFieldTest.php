<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusEasyCrudPlugin\Unit\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\Helper\Enum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class EnumFieldTest extends TestCase
{
    public function testNewCreatesInstance(): void
    {
        $field = EnumField::new('status');

        $this->assertInstanceOf(EnumField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testPropertyIsSetCorrectly(): void
    {
        $field = EnumField::new('status');

        $this->assertSame('status', $field->getAsDto()->getProperty());
    }

    public function testLabelIsSetCorrectly(): void
    {
        $field = EnumField::new('status', 'Order Status');

        $this->assertSame('Order Status', $field->getAsDto()->getLabel());
    }

    public function testFormTypeIsSetToChoiceType(): void
    {
        $field = EnumField::new('status');

        $this->assertSame(ChoiceType::class, $field->getAsDto()->getFormType());
    }

    public function testDefaultCustomOptionsAreSet(): void
    {
        $field = EnumField::new('status');

        $this->assertNull($field->getAsDto()->getCustomOption(EnumField::OPTION_ENUM));
        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_AS_BADGES));
        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_EXPANDED));
        $this->assertSame(EnumField::WIDGET_NATIVE, $field->getAsDto()->getCustomOption(EnumField::OPTION_WIDGET));
        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_ESCAPE_HTML_CONTENTS));
        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_ALLOW_MULTIPLE_CHOICES));
    }

    public function testSetEnumWithValidEnumClass(): void
    {
        $enumClass = new class('draft') extends Enum {
            public const DRAFT = 'draft';
            public const PUBLISHED = 'published';
        };

        $field = EnumField::new('status')->setEnum($enumClass::class);

        $this->assertSame($enumClass::class, $field->getAsDto()->getCustomOption(EnumField::OPTION_ENUM));
    }

    public function testSetEnumThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Enum class must be a valid class/');

        EnumField::new('status')->setEnum('NonExistent\EnumClass');
    }

    public function testSetEnumThrowsExceptionForInvalidEnumClass(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Enum class must be a valid class extending/');

        EnumField::new('status')->setEnum(\stdClass::class);
    }

    public function testRenderAsBadges(): void
    {
        $field = EnumField::new('status')->renderAsBadges(false);

        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_AS_BADGES));
    }

    public function testRenderExpanded(): void
    {
        $field = EnumField::new('status')->renderExpanded(true);

        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_EXPANDED));
    }

    public function testAllowMultipleChoices(): void
    {
        $field = EnumField::new('status')->allowMultipleChoices(true);

        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_ALLOW_MULTIPLE_CHOICES));
    }

    public function testEscapeHtml(): void
    {
        $field = EnumField::new('status')->escapeHtml(false);

        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_ESCAPE_HTML_CONTENTS));
    }

    public function testFluentInterface(): void
    {
        $enumClass = new class('draft') extends Enum {
            public const DRAFT = 'draft';
        };

        $field = EnumField::new('status')
            ->setEnum($enumClass::class)
            ->renderAsBadges(false)
            ->renderExpanded(true)
            ->allowMultipleChoices(true)
            ->escapeHtml(false);

        $this->assertInstanceOf(EnumField::class, $field);
        $this->assertSame($enumClass::class, $field->getAsDto()->getCustomOption(EnumField::OPTION_ENUM));
        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_AS_BADGES));
        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_RENDER_EXPANDED));
        $this->assertTrue($field->getAsDto()->getCustomOption(EnumField::OPTION_ALLOW_MULTIPLE_CHOICES));
        $this->assertFalse($field->getAsDto()->getCustomOption(EnumField::OPTION_ESCAPE_HTML_CONTENTS));
    }
}
