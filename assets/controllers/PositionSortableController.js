import { Controller } from '@hotwired/stimulus';

/**
 * Drag and drop handle of the PositionSortableField column (one controller per row).
 *
 * The row moves among its siblings, the rows of the list with the same `data-position-sortable-parent` (all the rows
 * of a flat list), together with its descendants in a tree (hidden while dragging). The arrow keys move it by one
 * sibling. Once dropped, the ids of the row and of its new previous and next siblings are posted to `url`; the page
 * is reloaded when the move fails, to show the saved order again.
 */
export default class extends Controller {
  static values = {
    url: String,
    csrfToken: String,
  };

  start(event) {
    if (event.button !== 0 || this.dragging) {
      return;
    }
    event.preventDefault();

    this.dragging = true;
    this.row = this.element.closest('tr');
    this.initialOrder = this.siblingIds();
    this.block = this.blockOf(this.row);
    this.block.slice(1).forEach((row) => { row.hidden = true; });
    this.row.classList.add('table-active');
    this.element.style.cursor = 'grabbing';

    this.onMove = (moveEvent) => this.move(moveEvent.clientY);
    this.onEnd = () => this.end();
    window.addEventListener('pointermove', this.onMove);
    window.addEventListener('pointerup', this.onEnd);
    window.addEventListener('pointercancel', this.onEnd);
  }

  move(y) {
    const sibling = this.siblings().find((row) => {
      const rect = row.getBoundingClientRect();
      return row !== this.row && y >= rect.top && y <= rect.bottom;
    });
    if (!sibling) {
      return;
    }

    // across the middle of the sibling, so that the row does not flicker between two places
    const rect = sibling.getBoundingClientRect();
    const isBelow = this.row.compareDocumentPosition(sibling) & Node.DOCUMENT_POSITION_FOLLOWING;
    if ((isBelow && y > rect.top + rect.height / 2) || (!isBelow && y < rect.top + rect.height / 2)) {
      this.swap(this.block, sibling);
    }
  }

  end() {
    window.removeEventListener('pointermove', this.onMove);
    window.removeEventListener('pointerup', this.onEnd);
    window.removeEventListener('pointercancel', this.onEnd);
    this.block.forEach((row) => { row.hidden = false; });
    this.row.classList.remove('table-active');
    this.element.style.cursor = 'grab';
    this.dragging = false;

    if (this.siblingIds() !== this.initialOrder) {
      this.save();
    }
  }

  keydown(event) {
    if (!['ArrowUp', 'ArrowDown'].includes(event.key) || this.dragging) {
      return;
    }
    event.preventDefault();

    this.row = this.element.closest('tr');
    const siblings = this.siblings();
    const sibling = siblings[siblings.indexOf(this.row) + ('ArrowUp' === event.key ? -1 : 1)];
    if (!sibling) {
      return;
    }

    this.swap(this.blockOf(this.row), sibling);
    this.save();
  }

  /**
   * Moves the sibling (and its descendants) to the other side of the block of the moved row: the moved row itself
   * stays in the document, so that it keeps the focus and the pointer events.
   */
  swap(block, sibling) {
    const siblingBlock = this.blockOf(sibling);
    if (block[0].compareDocumentPosition(sibling) & Node.DOCUMENT_POSITION_FOLLOWING) {
      block[0].before(...siblingBlock);
    } else {
      block.at(-1).after(...siblingBlock);
    }
  }

  async save() {
    const siblings = this.siblings();
    const index = siblings.indexOf(this.row);
    const table = this.row.closest('table');

    table.setAttribute('aria-busy', 'true');
    try {
      const response = await fetch(this.urlValue, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          id: this.idOf(this.row),
          previousId: index > 0 ? this.idOf(siblings[index - 1]) : null,
          nextId: index < siblings.length - 1 ? this.idOf(siblings[index + 1]) : null,
          _token: this.csrfTokenValue,
        }),
      });
      if (!response.ok) {
        throw new Error(`The new position was not saved (HTTP ${response.status}).`);
      }
    } catch (error) {
      window.location.reload();
    } finally {
      table.removeAttribute('aria-busy');
    }
  }

  siblings() {
    const parent = this.markerOf(this.row).dataset.positionSortableParent;

    return Array.from(this.row.parentElement.rows)
      .filter((row) => this.markerOf(row)?.dataset.positionSortableParent === parent);
  }

  siblingIds() {
    return this.siblings().map((row) => this.idOf(row)).join(',');
  }

  /**
   * The row and its descendants, the rows that follow it in a tree.
   */
  blockOf(row) {
    const parents = new Map(Array.from(row.parentElement.rows, (tableRow) => {
      const marker = this.markerOf(tableRow);
      return [marker?.dataset.positionSortableId, marker?.dataset.positionSortableParent];
    }));
    const id = this.idOf(row);
    const isDescendant = (otherRow) => {
      const seen = new Set();
      for (let parent = parents.get(this.idOf(otherRow)); parent && !seen.has(parent); parent = parents.get(parent)) {
        if (parent === id) {
          return true;
        }
        seen.add(parent);
      }
      return false;
    };

    const block = [row];
    for (let next = row.nextElementSibling; next && isDescendant(next); next = next.nextElementSibling) {
      block.push(next);
    }
    return block;
  }

  markerOf(row) {
    return row.querySelector('[data-position-sortable-id]');
  }

  idOf(row) {
    return this.markerOf(row)?.dataset.positionSortableId;
  }
}
