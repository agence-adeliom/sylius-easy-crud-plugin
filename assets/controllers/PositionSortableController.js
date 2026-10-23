import { Controller } from '@hotwired/stimulus';

/**
 * Drag and drop handle of the PositionSortableField column (one controller per row).
 *
 * The row moves among its siblings, the rows of the list with the same `data-position-sortable-parent` (all the rows
 * of a flat list), together with its descendants in a tree (hidden while dragging). While dragging, a copy of the row
 * follows the pointer and the row itself, emptied, leaves a gap where it will be dropped; the page scrolls when the
 * pointer nears the top or the bottom of the window; Escape cancels the move. The arrow
 * keys move it by one sibling. Once dropped, the ids of the row and of its new previous and next siblings are posted to `url`; the page
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
    this.initialRows = Array.from(this.row.parentElement.rows);
    this.initialOrder = this.siblingIds();
    this.ghost = this.createGhost(this.row, event.clientY);
    this.block = this.blockOf(this.row);
    this.block.slice(1).forEach((row) => { row.hidden = true; });
    this.showDropZone(true);
    document.body.style.cursor = 'grabbing';
    document.body.style.userSelect = 'none';

    this.onMove = (moveEvent) => this.move(moveEvent.clientY);
    this.onEnd = () => this.end();
    this.onCancel = () => this.end(true);
    this.onEscape = (keyEvent) => {
      if ('Escape' === keyEvent.key) {
        keyEvent.preventDefault();
        this.end(true);
      }
    };
    window.addEventListener('pointermove', this.onMove);
    window.addEventListener('pointerup', this.onEnd);
    window.addEventListener('pointercancel', this.onCancel);
    window.addEventListener('keydown', this.onEscape);
  }

  move(y) {
    this.pointerY = y;
    this.ghost.element.style.top = `${this.ghost.top + y - this.ghost.pointerY}px`;
    this.autoScroll();

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

  end(cancel = false) {
    window.removeEventListener('pointermove', this.onMove);
    window.removeEventListener('pointerup', this.onEnd);
    window.removeEventListener('pointercancel', this.onCancel);
    window.removeEventListener('keydown', this.onEscape);
    cancelAnimationFrame(this.scrollFrame);
    this.scrollFrame = null;
    this.ghost.element.remove();
    this.block.forEach((row) => { row.hidden = false; });
    this.showDropZone(false);
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
    this.dragging = false;

    if (cancel) {
      this.row.parentElement.append(...this.initialRows);
    } else if (this.siblingIds() !== this.initialOrder) {
      this.save();
    }
  }

  /**
   * Scrolls the page, one step per frame, while the pointer is near the top or the bottom of the window (faster
   * closer to the edge), and moves the row under the pointer again after each step.
   */
  autoScroll() {
    const edge = 60;
    let step = 0;
    if (this.pointerY < edge) {
      step = -Math.ceil((edge - this.pointerY) / 3);
    } else if (this.pointerY > window.innerHeight - edge) {
      step = Math.ceil((this.pointerY - window.innerHeight + edge) / 3);
    }
    if (0 === step || this.scrollFrame) {
      return;
    }

    this.scrollFrame = requestAnimationFrame(() => {
      this.scrollFrame = null;
      const scrollY = window.scrollY;
      // instant: the admin theme scrolls smoothly (`scroll-behavior: smooth`), scrollY would not change at once
      window.scrollBy({ top: step, behavior: 'instant' });
      if (this.dragging && window.scrollY !== scrollY) {
        this.move(this.pointerY);
      }
    });
  }

  /**
   * The dragged row as an empty slot of the same height, outlined: the place where the row will be dropped.
   */
  showDropZone(shown) {
    Array.from(this.row.cells).forEach((cell) => { cell.style.visibility = shown ? 'hidden' : ''; });
    Object.assign(this.row.style, shown
      ? {
          outline: '2px dashed var(--tblr-primary, #206bc4)',
          outlineOffset: '-2px',
          backgroundColor: 'var(--tblr-primary-lt, rgba(32, 107, 196, 0.08))',
        }
      : { outline: '', outlineOffset: '', backgroundColor: '' });
  }

  /**
   * A copy of the row in a table of its own, fixed above the list and moved with the pointer, with the widths of the
   * original cells.
   */
  createGhost(row, pointerY) {
    const rect = row.getBoundingClientRect();
    const table = document.createElement('table');
    table.className = row.closest('table').className;
    table.setAttribute('aria-hidden', 'true');
    Object.assign(table.style, {
      position: 'fixed',
      top: `${rect.top}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      margin: '0',
      zIndex: '1080',
      pointerEvents: 'none',
      opacity: '0.9',
      boxShadow: '0 0.5rem 1rem rgba(0, 0, 0, 0.15)',
      cursor: 'grabbing',
    });

    const copy = row.cloneNode(true);
    // a copy without Stimulus controller nor row marker: not another handle, not a sibling
    copy.querySelectorAll('[data-controller], [data-action], [data-position-sortable-id]').forEach((element) => {
      element.removeAttribute('data-controller');
      element.removeAttribute('data-action');
      element.removeAttribute('data-position-sortable-id');
    });
    Array.from(row.cells).forEach((cell, index) => {
      copy.cells[index].style.width = `${cell.getBoundingClientRect().width}px`;
    });
    // opaque over the list: the surface colour of the Sylius admin theme (Tabler)
    copy.style.backgroundColor = 'var(--tblr-bg-surface, #fff)';

    const body = document.createElement('tbody');
    body.append(copy);
    table.append(body);
    document.body.append(table);

    return { element: table, top: rect.top, pointerY };
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
