<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\EnumField;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\Helper\Enum;
use Sylius\Resource\Model\ResourceInterface;

/**
 * This class was copied from EasyAdmin Symfony bundle and adapted for this Sylius plugin
 */
final class EnumConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return EnumField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        $isExpanded = $field->getCustomOption(EnumField::OPTION_RENDER_EXPANDED);
        $isMultiple = $field->getCustomOption(EnumField::OPTION_ALLOW_MULTIPLE_CHOICES);

        $choices = $this->getChoices($field->getCustomOption(EnumField::OPTION_ENUM), $field);

        if (empty($choices)) {
            throw new \InvalidArgumentException(sprintf('The "%s" choice field must define its possible choices using the setEnum() method.', $field->getProperty()));
        }

        $field->setFormTypeOptionIfNotSet('choices', $choices);
        $field->setFormTypeOptionIfNotSet('expanded', $isExpanded);
        $field->setFormTypeOptionIfNotSet('multiple', $isMultiple);
        $field->setFormTypeOptionIfNotSet('block_prefix', 'enum');

        $field->setCustomOption(EnumField::OPTION_WIDGET, EnumField::WIDGET_NATIVE);

        $field->setFormTypeOptionIfNotSet('placeholder', '');

        // the value of this form option must be a string to properly propagate it as an HTML attribute value
        $field->setFormTypeOption('attr.data-ea-autocomplete-render-items-as-html', $field->getCustomOption(EnumField::OPTION_ESCAPE_HTML_CONTENTS) ? 'false' : 'true');
    }

    /**
     * @return array<string, mixed>
     */
    private function getChoices(Enum|string|null $enum, FieldDto $field): array
    {
        if (null === $enum) {
            return [];
        }

        if (is_string($enum) && !class_exists($enum)) {
            return [];
        }

        $choicesEnum = $enum::toArray();
        $choices = [];
        foreach ($choicesEnum as $v) {
            $choices[sprintf('sylius_easy_crud_plugin.enum.%s.%s', $field->getProperty(), $v)] = $v;
        }

        return $choices;
    }

    public function formatValue(FieldDto $field, mixed $value): mixed
    {
        return $value;
    }
}
