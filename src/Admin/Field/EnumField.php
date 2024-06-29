<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Helper\Enum;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class EnumField implements FieldInterface
{
    use FieldTrait;

    /**
     * @var string
     */
    public const OPTION_ENUM = 'enum';

    /**
     * @var string
     */
    public const OPTION_RENDER_AS_BADGES = 'renderAsBadges';

    /**
     * @var string
     */
    public const OPTION_RENDER_EXPANDED = 'renderAsExpanded';

    /**
     * @var string
     */
    public const OPTION_ALLOW_MULTIPLE_CHOICES = 'allowMultipleChoices';

    /**
     * @var string
     */
    public const OPTION_WIDGET = 'widget';

    /**
     * @var string
     */
    public const OPTION_ESCAPE_HTML_CONTENTS = 'escapeHtml';

    /**
     * @var string[]
     */
    public const VALID_BADGE_TYPES = ['success', 'warning', 'danger', 'info', 'primary', 'secondary', 'light', 'dark'];

    /**
     * @var string
     */
    public const WIDGET_AUTOCOMPLETE = 'autocomplete';

    /**
     * @var string
     */
    public const WIDGET_NATIVE = 'native';

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceType::class)
            ->addFormTheme('@SyliusEasyCrudPlugin/field/enum/form.html.twig')
            ->setCustomOption(self::OPTION_ENUM, null)
            ->setCustomOption(self::OPTION_RENDER_AS_BADGES, true)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false)
            ->setCustomOption(self::OPTION_WIDGET, self::WIDGET_NATIVE)
            ->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, true)
            ->setCustomOption(self::OPTION_ALLOW_MULTIPLE_CHOICES, false)
        ;
    }

    /**
     * Given enum must follow the same format used in Symfony Forms:.
     */
    public function setEnum(string $enumFcqn): self
    {
        if (!class_exists($enumFcqn) || !is_a($enumFcqn, Enum::class, true)) {
            throw new InvalidConfigurationException(sprintf('Enum class must be a valid class extending %s. "%s" given.', Enum::class, $enumFcqn));
        }

        $this->setCustomOption(self::OPTION_ENUM, $enumFcqn);

        return $this;
    }

    /**
     * Possible values of $badgeSelector:
     *   * true: all values are displayed as 'secondary' badges
     *   * false: no badges are displayed; values are displayed as regular text
     *   * array: [$fieldValue => $badgeType, ...] (e.g. ['foo' => 'primary', 7 => 'warning', 'cancelled' => 'danger'])
     *   * callable: function(FieldDto $field): string { return '...' }
     *     (e.g. function(FieldDto $field) { return $field->getValue() < 10 ? 'warning' : 'primary'; }).
     *
     * Possible badge types: 'success', 'warning', 'danger', 'info', 'primary', 'secondary', 'light', 'dark'
     */
    public function renderAsBadges(?bool $badgeSelector = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_AS_BADGES, $badgeSelector);

        return $this;
    }

    public function renderExpanded(bool $expanded = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_EXPANDED, $expanded);

        return $this;
    }

    public function allowMultipleChoices(bool $allowMultipleChoices = true): self
    {
        $this->setCustomOption(self::OPTION_ALLOW_MULTIPLE_CHOICES, $allowMultipleChoices);

        return $this;
    }

    public function escapeHtml(bool $escape = true): self
    {
        $this->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, $escape);

        return $this;
    }
}
