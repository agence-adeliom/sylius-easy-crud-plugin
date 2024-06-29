<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\ChoiceMaskField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Config\Crud;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use function Symfony\Component\String\u;

/**
 * * Inspired by EasyAdmin Symfony Bundle
 */
final class ChoiceMaskConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return ChoiceMaskField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null, ?string $pageName = null): void
    {
        $isExpanded = $field->getCustomOption(ChoiceMaskField::OPTION_RENDER_EXPANDED);

        $choices = $this->getChoices(
            $field->getCustomOption(ChoiceMaskField::OPTION_CHOICES),
        ); //, $entityDto, $field
        $map = $this->getMap(
            $field->getCustomOption(ChoiceMaskField::OPTION_MAP),
        ); // , $entityDto, $field
        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field must define its possible choices using the setChoices() method.', $field->getProperty()));
        }

        if (empty($map)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field must define its fields map using the setMap() method.', $field->getProperty()));
        }

        $field->setFormTypeOptionIfNotSet('choices', $choices);
        $field->setFormTypeOptionIfNotSet('map', $map);
        $field->setFormTypeOptionIfNotSet('expanded', $isExpanded);
        $field->setFormTypeOptionIfNotSet('isTranslation', $field->getCustomOption(ChoiceMaskField::OPTION_IS_TRANSLATION));

        $field->setCustomOption(ChoiceMaskField::OPTION_WIDGET, ChoiceMaskField::WIDGET_NATIVE);

        $field->setFormTypeOptionIfNotSet('placeholder', '');

        // the value of this form option must be a string to properly propagate it as an HTML attribute value
        $field->setFormTypeOption(
            'attr.data-ea-autocomplete-render-items-as-html',
            ($field->getCustomOption(ChoiceMaskField::OPTION_ESCAPE_HTML_CONTENTS) ? 'false' : 'true')
        );

        $fieldValue = $field->getValue();
        $isIndexOrDetail = $pageName === Crud::PAGE_INDEX;
        if (null === $fieldValue || !$isIndexOrDetail) {
            return;
        }

        $badgeSelector = $field->getCustomOption(ChoiceMaskField::OPTION_RENDER_AS_BADGES);
        $isRenderedAsBadge = null !== $badgeSelector && false !== $badgeSelector;

        $selectedChoices = [];
        $flippedChoices = array_flip($choices);
        // $value is a scalar for single selections and an array for multiple selections
        foreach (array_values((array) $fieldValue) as $selectedValue) {
            if (null !== $selectedChoice = $flippedChoices[$selectedValue] ?? null) {
                $choiceValue = $selectedChoice;
                $selectedChoices[] = $isRenderedAsBadge
                    ? sprintf('<span class="%s">%s</span>', $this->getBadgeCssClass($badgeSelector, $selectedValue, $field), $choiceValue)
                    : $choiceValue;
            }
        }

        $field->setFormattedValue(implode($isRenderedAsBadge ? '' : ', ', $selectedChoices));
    }

    /**
     * @return array<int, mixed>
     */
    private function getChoices(mixed $choiceGenerator): array
    {
        if (null === $choiceGenerator) {
            return [];
        }

        if (\is_array($choiceGenerator)) {
            return $choiceGenerator;
        }

        return [];
        //return $choiceGenerator($entity->getInstance(), $field);
    }

    /**
     * @return array<int, mixed>
     */
    private function getMap(mixed $mapGenerator): array
    {
        if (null === $mapGenerator) {
            return [];
        }

        if (\is_array($mapGenerator)) {
            return $mapGenerator;
        }

        return [];
        //return $mapGenerator($entity->getInstance(), $field);
    }

    private function getBadgeCssClass(mixed $badgeSelector, mixed $value, FieldDto $field): string
    {
        $commonBadgeCssClass = 'badge';

        if (true === $badgeSelector) {
            $badgeType = 'badge-secondary';
        } elseif (\is_array($badgeSelector)) {
            $badgeType = $badgeSelector[$value] ?? 'badge-secondary';
        } elseif (\is_callable($badgeSelector)) {
            $badgeType = $badgeSelector($value, $field);
            if (!\in_array($badgeType, ChoiceMaskField::VALID_BADGE_TYPES, true)) {
                throw new \RuntimeException(sprintf('The value returned by the callable passed to the "renderAsBadges()" method must be one of the following valid badge types: "%s" ("%s" given).', implode(', ', ChoiceMaskField::VALID_BADGE_TYPES), $badgeType));
            }
        }

        $badgeTypeCssClass = empty($badgeType) ? '' : u($badgeType)->ensureStart('badge-')->toString();

        return $commonBadgeCssClass . ' ' . $badgeTypeCssClass;
    }
}
