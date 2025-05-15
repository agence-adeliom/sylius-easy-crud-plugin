window.addEventListener('DOMContentLoaded', () => {

    class CollectionForm {
        constructor(element) {
            this.addItem = this.addItem.bind(this);
            this.updateItem = this.updateItem.bind(this);
            this.deleteItem = this.constructor.deleteItem;
            this.updatePrototype = this.updatePrototype.bind(this);

            this.element = element;
            this.element.classList.add('processed');
            this.list = this.element.querySelector('[data-form-collection="list"]');
            this.count = this.list.children.length;
            this.lastChoice = null;

            this.element.addEventListener('click', (event) => {
                if (event.target.matches('[data-form-collection="add"]:last-child')) {
                    this.addItem(event);
                    event.stopPropagation();
                    return;
                }
                if (event.target.matches('[data-form-collection="delete"]')) {
                    this.deleteItem(event);
                    event.stopPropagation();
                    return;
                }
            });

            this.element.addEventListener('change', (event) => {
                if (event.target.matches('[data-form-collection="update"]')) {
                    this.updateItem(event);
                }
            });

            document.addEventListener('change', (event) => {
                if (event.target.matches('[data-form-prototype="update"]')) {
                    this.updatePrototype(event);
                }
            });

            document.addEventListener('collection-form-add', (event) => {
                const addedElement = event.detail;
                addedElement.querySelectorAll('[data-form-type="collection"]:not(.processed)').forEach((child) => {
                    new CollectionForm(child);
                });
                document.dispatchEvent(new CustomEvent('dom-node-inserted', { detail: addedElement }));
            });
        }

        /**
         * Add an item to the collection.
         * @param event
         */
        addItem(event) {
            event.preventDefault();

            let prototype = this.element.dataset.prototype;
            const prototypeName = new RegExp(this.element.dataset.prototypeName, 'g');

            prototype = prototype.replace(prototypeName, this.count);

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = prototype.trim();
            const newElement = tempDiv.firstChild;

            this.list.appendChild(newElement);

            // Execute potential prototype scripts
            const scripts = newElement.querySelectorAll('script');
            scripts.forEach((script) => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                } else {
                    newScript.textContent = script.textContent;
                }
                document.body.append(newScript);
            });

            tempDiv.remove();

            this.count += 1;

            document.dispatchEvent(new CustomEvent('collection-form-add', { detail: newElement }));
        }

        /**
         * Update an item in the collection.
         * @param event
         */
        updateItem(event) {
            event.preventDefault();
            const element = event.target;
            const url = element.dataset.formUrl;
            const value = element.value;
            const container = element.closest('[data-form-collection="item"]');
            const index = container.dataset.formCollectionIndex;
            const position = container.dataset.formCollectionIndex;

            if (url) {
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: value, position }),
                })
                    .then((response) => response.text())
                    .then((html) => {
                        container.innerHTML = html;
                    });
            } else {
                const prototypeElement = this.element.querySelector(`[data-form-prototype="${value}"]`);
                const prototypeName = new RegExp(prototypeElement.dataset.subprototypeName, 'g');

                let prototype = prototypeElement.value.replace(prototypeName, index);

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = prototype.trim();
                const newElement = tempDiv.firstChild;

                container.replaceWith(newElement);

                // Execute potential prototype scripts
                const scripts = newElement.querySelectorAll('script');
                scripts.forEach((script) => {
                    const newScript = document.createElement('script');
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.textContent = script.textContent;
                    }
                    document.body.append(newScript);
                });

                tempDiv.remove();
            }

            document.dispatchEvent(new CustomEvent('collection-form-update', { detail: element }));
        }

        /**
         * Delete an item from the collection.
         * @param event
         */
        static deleteItem(event) {
            event.preventDefault();

            const item = event.target.closest('[data-form-collection="item"]');
            if (item) {
                item.remove();
            }
            this.count -= 1;

            document.dispatchEvent(new CustomEvent('collection-form-delete', { detail: event.target }));
        }

        /**
         * Update the prototype.
         * @param event
         */
        updatePrototype(event) {
            const target = event.target;
            let prototypeName = target.value;

            if (target.dataset.formPrototypePrefix !== undefined) {
                prototypeName = target.dataset.formPrototypePrefix + prototypeName;
            }

            if (this.lastChoice !== null && this.lastChoice !== prototypeName) {
                this.list.innerHTML = '';
            }

            this.lastChoice = prototypeName;

            const prototypeElement = this.element.querySelector(`[data-form-prototype="${prototypeName}"]`);
            if (prototypeElement) {
                this.element.dataset.prototype = prototypeElement.value;
            }
        }
    }

    /*
     * Plugin definition
     */

    document.querySelectorAll('[data-form-type="collection"]').forEach((element) => {
        new CollectionForm(element);
    });
});
