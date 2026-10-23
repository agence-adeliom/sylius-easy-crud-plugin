<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Admin\Field;

/**
 * Options shared by the collections rendered with the sortable collection form type ({@see SortableCollectionField},
 * {@see CollectionField}); the custom options are the ones of {@see SortableCollectionField}.
 */
trait CollectionFieldTrait
{
    public function setEntryType(string $type): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_ENTRY_TYPE, $type);

        return $this;
    }

    public function allowAdd(bool $allow = true): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_ALLOW_ADD, $allow);

        return $this;
    }

    public function allowDelete(bool $allow = true): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_ALLOW_DELETE, $allow);

        return $this;
    }

    /**
     * Set this option to TRUE if the collection items are complex form types
     * composed of several form fields (this plugin applies a special rendering to make them look better).
     */
    public function setEntryIsComplex(bool $isComplex): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_ENTRY_IS_COMPLEX, $isComplex);

        return $this;
    }

    public function showEntryLabel(bool $showLabel = true): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_SHOW_ENTRY_LABEL, $showLabel);

        return $this;
    }

    /**
     * Title of each entry's accordion header, computed from the entry data
     * (the data rendered by the display_as_string filter when not set, or when the callable returns null).
     *
     * @param callable(mixed): ?string $entryTitle
     */
    public function setEntryTitle(callable $entryTitle): self
    {
        $this->setFormTypeOption('entry_title', $entryTitle);

        return $this;
    }

    public function renderExpanded(bool $renderExpanded = true): self
    {
        $this->setCustomOption(SortableCollectionField::OPTION_RENDER_EXPANDED, $renderExpanded);

        return $this;
    }
}
