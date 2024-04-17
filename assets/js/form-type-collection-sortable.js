import Sortable from 'sortablejs';

const SortableCollectionProperty = {
  queryParentsSelector(selector, elm) {
    for (let parent = elm.parentElement; parent != null; parent = parent.parentElement) {
      if (parent.matches && parent.matches(selector)) return parent;
    }
    return null;
  },
  queryParentsSelectorAll(selector, elm) {
    const result = [];
    for (let parent = elm.parentElement; parent != null; parent = parent.parentElement) {
      if (typeof selector === 'undefined' || selector === '') {
        result.push(parent);
      } else if (parent.matches && parent.matches(selector)) {
        result.push(parent);
      }
    }
    return result;
  },
  handleAddButton(addButton, collection) {
    addButton.addEventListener('click', () => {
      SortableCollectionProperty.updateCollectionItemCssClasses(collection);
      SortableCollectionProperty.updateCollectionSortable(collection);
    });

    addButton.classList.add('processed');
  },
  updateCollectionSortable(collection) {
    if (null === collection) {
      return;
    }

    if (document.getElementById(collection.dataset.formTypeId)) {
      const formWrapper = document.getElementById(collection.dataset.formTypeId);
      const sortableWrapper = formWrapper.querySelector('.collection-list');

      if (collection.sortable) {
        collection.sortable.destroy();
        collection.sortable = null;
      }

      collection.sortable = Sortable.create(sortableWrapper, {
        handle: `[drag-handler="${collection.dataset.formTypeId}"]`,
        direction: 'vertical',
        onEnd(evt) {
          SortableCollectionProperty.updateCollectionItemCssClasses(collection);
        },
      });
    }
  },
  updateCollectionItemCssClasses(collection) {
    if (collection === null) {
      return;
    }

    const collectionItems = collection.querySelectorAll(`.field-sortable_collection-item[data-form-type-parent-id="${collection.dataset.formTypeParentId}"]`);

    const fullName = collection.dataset.syliusCollectionFieldFullName;

    let hasToIncrement = false;

    collectionItems.forEach((item, key) => {
      item.querySelectorAll('[name]')
        .forEach((input) => {
          if (!input.name) {
            return;
          }
          const name = input.name.replace(fullName, '');
          const index = /^\[\d+\]/g.exec(name);
          if (index) {
            const i = hasToIncrement ? key + 1 : key;
            const child = name.replace(index, '');
            // eslint-disable-next-line no-param-reassign
            input.name = `${fullName}[${i}]${child}`;
          }
        });
    });

    collectionItems.forEach((item) => item.classList.remove('field-sortable_collection-item-first', 'field-sortable_collection-item-last'));

    const firstElement = collectionItems[0];
    if (undefined === firstElement) {
      return;
    }
    firstElement.classList.add('field-sortable_collection-item-first');

    const lastElement = collectionItems[collectionItems.length - 1];
    if (undefined === lastElement) {
      return;
    }
    lastElement.classList.add('field-sortable_collection-item-last');
  },
};

const sortableCollectionHandler = function (event) {
  document.querySelectorAll('.field-sortable_collection-add-button:not(.processed)')
    .forEach((addButton) => {
      const collection = addButton.closest('[data-sylius-collection-field]');
      if (!collection || addButton.classList.contains('processed')) {
        return;
      }
      collection.dataset.level = SortableCollectionProperty.queryParentsSelectorAll('[data-sylius-collection-field]', addButton).length;
      SortableCollectionProperty.handleAddButton(addButton, collection);
      SortableCollectionProperty.updateCollectionItemCssClasses(collection);
      SortableCollectionProperty.updateCollectionSortable(collection);
    });

  document.querySelectorAll('.field-sortable_collection-add-button[disabled]')
    .forEach((addButton) => {
      addButton.disabled = false;
    });

  document.querySelectorAll('.field-sortable_collection-delete-button')
    .forEach((deleteButton) => {
      deleteButton.addEventListener('click', () => {
        const collection = deleteButton.closest('[data-sylius-collection-field]');

        SortableCollectionProperty.updateCollectionItemCssClasses(collection);
        SortableCollectionProperty.updateCollectionSortable(collection);
      });
    });
};

if (document.readyState == 'loading') {
  // still loading, wait for the event
  window.addEventListener('DOMContentLoaded', sortableCollectionHandler);
} else {
  sortableCollectionHandler();
}
