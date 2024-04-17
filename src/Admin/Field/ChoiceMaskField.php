<?php

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\Form\ChoiceMaskType;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;

final class ChoiceMaskField implements FieldInterface
{
    use FieldTrait;

    /**
     * @var string
     */
    public const OPTION_CHOICES = 'choices';

    /**
     * @var string
     */
    public const OPTION_MAP = 'map';

    /**
     * @var string
     */
    public const OPTION_RENDER_AS_BADGES = 'renderAsBadges';

    /**
     * @var string
     */
    public const OPTION_RENDER_EXPANDED = 'renderExpanded';

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
     * @var bool
     */
    public const OPTION_IS_TRANSLATION = false;

    /**
     * @param string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->addFormTheme('@SyliusEasyCrudPlugin/field/choicemask/form.html.twig')
            //->setShowTemplatePath('@SyliusEasyCrudPlugin/field/choicemask/show.html.twig')
            //->setGridTemplatePath('@SyliusEasyCrudPlugin/field/choicemask/grid.html.twig')
            ->setFormType(ChoiceMaskType::class)
            ->onlyOnForms()
            ->addCssClass('field-select')
            ->setDefaultColumns('') // this is set dynamically in the field configurator
            ->setCustomOption(self::OPTION_CHOICES, null)
            ->setCustomOption(self::OPTION_MAP, [])
            ->setCustomOption(self::OPTION_RENDER_AS_BADGES, null)
            ->setCustomOption(self::OPTION_RENDER_EXPANDED, false)
            ->setCustomOption(self::OPTION_WIDGET, self::WIDGET_NATIVE)
            ->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, true)
            ->setCustomOption(self::OPTION_IS_TRANSLATION, self::OPTION_IS_TRANSLATION);
    }

    /**
     * Given choices must follow the same format used in Symfony Forms:
     * ['Label visible to users' => 'submitted_value', ...].
     *
     * In addition to an array, you can use a PHP callback, which is passed the instance
     * of the current entity (it can be null) and the FieldDto as the second argument:
     * ->setChoices(fn () => ['foo' => 1, 'bar' => 2])
     * ->setChoices(fn (?MyEntity $foo) => $foo->someField()->getChoices())
     * ->setChoices(fn (?MyEntity $foo, FieldDto $field) => ...)
     */
    public function setChoices($choiceGenerator): self
    {
        if (!\is_array($choiceGenerator) && !\is_callable($choiceGenerator)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s" method must be an array or a closure ("%s" given).', __METHOD__, \gettype($choiceGenerator)));
        }

        $this->setCustomOption(self::OPTION_CHOICES, $choiceGenerator);

        return $this;
    }

    /**
     * Given choices must follow the same format used in Symfony Forms:
     * ['submitted_value' => ['visible_field_name'], ...].
     *
     * In addition to an array, you can use a PHP callback, which is passed the instance
     * of the current entity (it can be null) and the FieldDto as the second argument:
     * ->setMap(fn () => ['foo' => ['bar']])
     * ->setMap(fn (?MyEntity $foo) => $foo->someField()->getChoices())
     * ->setMap(fn (?MyEntity $foo, FieldDto $field) => ...)
     */
    public function setMap($mapGenerator): self
    {
        if (!\is_array($mapGenerator) && !\is_callable($mapGenerator)) {
            throw new \InvalidArgumentException(sprintf('The argument of the "%s" method must be an array or a closure ("%s" given).', __METHOD__, \gettype($mapGenerator)));
        }

        $this->setCustomOption(self::OPTION_MAP, $mapGenerator);

        return $this;
    }

    public function renderExpanded(bool $expanded = true): self
    {
        $this->setCustomOption(self::OPTION_RENDER_EXPANDED, $expanded);

        return $this;
    }

    public function escapeHtml(bool $escape = true): self
    {
        $this->setCustomOption(self::OPTION_ESCAPE_HTML_CONTENTS, $escape);

        return $this;
    }

    public function isTranslation(bool $isTranslation = false): self
    {
        $this->setCustomOption(self::OPTION_IS_TRANSLATION, $isTranslation);

        return $this;
    }
}
