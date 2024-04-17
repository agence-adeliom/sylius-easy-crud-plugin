<?php

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field\Configurator;

use Adeliom\SyliusEasyCrudPlugin\Admin\Field\SortableCollectionField;
use Doctrine\ORM\PersistentCollection;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Field\FieldConfiguratorInterface;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\EntityDto;
use Adeliom\SyliusEasyCrudPlugin\CrudFactory\Dto\FieldDto;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

use function Symfony\Component\String\u;

/**
 * * Inspired by EasyAdmin Symfony Bundle
 */
final class SortableCollectionConfigurator implements FieldConfiguratorInterface
{
    public function supports(FieldDto $field, ?ResourceInterface $resource = null): bool
    {
        return SortableCollectionField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, ?ResourceInterface $resource = null): void
    {
        if (null !== $entryTypeFqcn = $field->getCustomOptions()->get(SortableCollectionField::OPTION_ENTRY_TYPE)) {
            $field->setFormTypeOption('entry_type', $entryTypeFqcn);
        }

        $autocompletableFormTypes = [
            CountryType::class,
            CurrencyType::class,
            LanguageType::class,
            LocaleType::class,
            TimezoneType::class
        ];
        if (\in_array($entryTypeFqcn, $autocompletableFormTypes, true)) {
            $field->setFormTypeOption('entry_options.attr.data-ea-widget', 'ea-autocomplete');
        }

        $field->setFormTypeOption('allow_drag', $field->getCustomOptions()->get(SortableCollectionField::OPTION_ALLOW_DRAG));
        $field->setFormTypeOption('allow_add', $field->getCustomOptions()->get(SortableCollectionField::OPTION_ALLOW_ADD));
        $field->setFormTypeOption('allow_delete', $field->getCustomOptions()->get(SortableCollectionField::OPTION_ALLOW_DELETE));
        $field->setFormTypeOptionIfNotSet('by_reference', false);
        $field->setFormTypeOptionIfNotSet('delete_empty', true);

        // TODO: check why this label (hidden by default) is not working properly
        // (generated values are always the same for all elements)
        $field->setFormTypeOptionIfNotSet('entry_options.label', $field->getCustomOptions()->get(SortableCollectionField::OPTION_SHOW_ENTRY_LABEL));

        // collection items range from a simple <input text> to a complex multi-field form
        // the 'entryIsComplex' setting tells if the collection item is so complex that needs a special
        // rendering not applied to simple collection items
        if (null === $field->getCustomOption(SortableCollectionField::OPTION_ENTRY_IS_COMPLEX)) {
            $definesEntryType = null !== $entryTypeFqcn = $field->getCustomOption(SortableCollectionField::OPTION_ENTRY_TYPE);
            $isSymfonyCoreFormType = null !== u($entryTypeFqcn ?? '')->indexOf('Symfony\Component\Form\Extension\Core\Type');
            $isComplexEntry = $definesEntryType && !$isSymfonyCoreFormType;

            $field->setCustomOption(SortableCollectionField::OPTION_ENTRY_IS_COMPLEX, $isComplexEntry);
        }

        $field->setFormattedValue($this->formatCollection($field));
    }

    private function formatCollection(FieldDto $field)
    {
        $doctrineMetadata = $field->getDoctrineMetadata();
        if ('array' !== $doctrineMetadata->get('type') && !$field->getValue() instanceof PersistentCollection) {
            return $this->countNumElements($field->getValue());
        }

        $collectionItemsAsText = [];
        foreach ($field->getValue() ?? [] as $item) {
            if (!\is_string($item) && !(\is_object($item) && method_exists($item, '__toString'))) {
                return $this->countNumElements($field->getValue());
            }

            $collectionItemsAsText[] = (string)$item;
        }

        $isDetailAction = false;

        return u(', ')->join($collectionItemsAsText)->truncate($isDetailAction ? 512 : 32, '…')->toString();
    }

    private function countNumElements($collection): int
    {
        if (null === $collection) {
            return 0;
        }

        if (is_countable($collection)) {
            return \count($collection);
        }

        if ($collection instanceof \Traversable) {
            return iterator_count($collection);
        }

        return 0;
    }
}
