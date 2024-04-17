<?php

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldTrait;
use Adeliom\SyliusEasyCrudPlugin\Form\FieldResourceTranslationsType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

final class TranslationField implements FieldInterface
{
    use FieldTrait;

    protected ?iterable $fields = null;

    public static function new(string $propertyName, ?string $label = null, $fieldsConfig = []): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(FieldResourceTranslationsType::class)
            ->addFormThemes(FieldResourceTranslationsType::configureAdminFormThemes())
            ->setShowTemplatePath('@SyliusEasyCrudPlugin/field/translation/show.html.twig')
            ->setCustomOption('fieldsDto', [])
            ->setFormTypeOption('fields', [])
            ->setFormTypeOption('entry_type', FormType::class);
    }

    public function addField(FieldInterface $field): self
    {
        if (is_null($this->fields)) {
            $this->fields = [];
        }
        $this->fields[] = $field;
        $this->setCustomOption('fieldsDto', $this->fields);

        return $this;
    }

    public function restrictToLocales(array $locales): self
    {
        if (is_array($locales) && count($locales) > 0) {
            $this->setFormTypeOption('entries', $locales);
        }
        return $this;
    }

}
