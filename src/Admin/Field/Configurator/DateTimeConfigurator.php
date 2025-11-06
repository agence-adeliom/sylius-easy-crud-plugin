<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\DateTimeField;
use Adeliom\SyliusEasyCrudPlugin\Admin\Field\TimeField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\Intl\IntlFormatter;
use DateTimeInterface;
use Sylius\Resource\Model\ResourceInterface;

final class DateTimeConfigurator implements FieldConfiguratorInterface
{
    private IntlFormatter $intlFormatter;

    public function __construct(IntlFormatter $intlFormatter)
    {
        $this->intlFormatter = $intlFormatter;
    }

    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return \in_array($field->getFieldFqcn(), [DateTimeField::class, DateField::class, TimeField::class], true);
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null, ?string $pageName = null): void
    {
        // we don't require this PHP extension in composer.json because it's not mandatory to display
        // date/time fields in backends, so this is not a hard dependency
        if (!\extension_loaded('intl')) {
            throw new \LogicException('When using date/time fields in backends, you must install and enable the PHP Intl extension, which is used to format date/time values.');
        }

        $widgetOption = $field->getCustomOption(DateTimeField::OPTION_WIDGET);
        if (DateTimeField::WIDGET_NATIVE === $widgetOption) {
            $field->setFormTypeOption('widget', 'single_text');
            $field->setFormTypeOption('html5', true);
        } elseif (DateTimeField::WIDGET_CHOICE === $widgetOption) {
            $field->setFormTypeOption('widget', 'choice');
            $field->setFormTypeOption('html5', true);
        } elseif (DateTimeField::WIDGET_TEXT === $widgetOption) {
            $field->setFormTypeOption('widget', 'single_text');
            $field->setFormTypeOption('html5', false);
        }

        // check if the property is immutable
        $field->getCustomOption('immutable');
        // $isImmutableDateTime = \in_array($doctrineDataType, [Types::DATETIMETZ_IMMUTABLE,
        // Types::DATETIME_IMMUTABLE, Types::DATE_IMMUTABLE, Types::TIME_IMMUTABLE], true);
        if ($field->getCustomOption(DateField::IMMUTABLE)) {
            $field->setFormTypeOptionIfNotSet('input', 'datetime_immutable');
        }
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        $timezone = $field->getCustomOption(DateTimeField::OPTION_TIMEZONE) ?? null;

        assert(
            $timezone instanceof \DateTimeZone || null === $timezone || false === $timezone || is_string($timezone),
            'The timezone option must be an instance of \DateTimeZone, string, null or false.',
        );

        $dateFormat = null;
        $timeFormat = null;
        $icuDateTimePattern = '';
        $formattedValue = '';

        assert($value instanceof DateTimeInterface || null === $value, 'The value to format is not a valid DateTimeInterface instance.');

        if (DateTimeField::class === $field->getFieldFqcn()) {
            [$defaultDatePattern, $defaultTimePattern] = ['medium', 'medium'];
            /** @var string $datePattern */
            $datePattern = $field->getCustomOption(DateTimeField::OPTION_DATE_PATTERN) ?? $defaultDatePattern;
            $timePattern = $field->getCustomOption(DateTimeField::OPTION_TIME_PATTERN) ?? $defaultTimePattern;
            if (\in_array($datePattern, DateTimeField::VALID_DATE_FORMATS, true)) {
                $dateFormat = $datePattern;
                $timeFormat = $timePattern;
            } else {
                $icuDateTimePattern = $datePattern;
            }

            assert(is_string($timeFormat) || null === $timeFormat, 'Wrong format for $timeFormat.');

            $formattedValue = $this->intlFormatter->formatDateTime($value, $dateFormat, $timeFormat, $icuDateTimePattern, $timezone);
        } elseif (DateField::class === $field->getFieldFqcn()) {
            /** @var string $dateFormatOrPattern */
            $dateFormatOrPattern = $field->getCustomOption(DateField::OPTION_DATE_PATTERN) ?? 'medium';
            if (\in_array($dateFormatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
                $dateFormat = $dateFormatOrPattern;
            } else {
                $icuDateTimePattern = $dateFormatOrPattern;
            }

            $formattedValue = $this->intlFormatter->formatDate($value, $dateFormat, $icuDateTimePattern, $timezone);
        } elseif (TimeField::class === $field->getFieldFqcn()) {
            /** @var string $timeFormatOrPattern */
            $timeFormatOrPattern = $field->getCustomOption(TimeField::OPTION_TIME_PATTERN) ?? 'medium';
            if (\in_array($timeFormatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
                $timeFormat = $timeFormatOrPattern;
            } else {
                $icuDateTimePattern = $timeFormatOrPattern;
            }

            $formattedValue = $this->intlFormatter->formatTime($value, $timeFormat, $icuDateTimePattern, $timezone);
        }

        return $formattedValue;
    }
}
