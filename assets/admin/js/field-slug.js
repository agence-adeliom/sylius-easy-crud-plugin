window.addEventListener('DOMContentLoaded', () => {

    const slugify = function (str, separator = "-") {
        return str
            .toString()
            .normalize('NFD') // split an accented letter in the base letter and the accent
            .replace(/[\u0300-\u036f]/g, '') // remove all previously split accents
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9 ]/g, '') // remove all chars not letters, numbers and spaces (to be replaced)
            .replace(/\s+/g, separator);
    };

    const slugWrappers = [];
    document.querySelectorAll('[data-slug-for]').forEach((elem) => {
        slugWrappers.push(elem.parentNode);
    });

    for (const wrapper of slugWrappers) {
        const slugInput = wrapper.querySelector('[name*="[slug]"]');
        const nameInputName = slugInput.name.replace("[slug]", "[name]");
        const nameInput = document.querySelector(`[name="${nameInputName}"]`);
        const lockButton = wrapper.querySelector('.toggle-slug-modification');

        const updateSlug = function () {
            if (slugInput.getAttribute('readonly') === 'readonly') {
                return;
            }

            slugInput.value = slugify(nameInput.value);

            if (slugInput.parentNode.classList.contains('error')) {
                slugInput.parentNode.classList.remove('error');
                slugInput.parentNode.querySelector('.sylius-validation-error').remove();
            }
        };

        const toggleSlugModification = function (button) {
            if (slugInput.hasAttribute('readonly')) {
                slugInput.removeAttribute('readonly');
                slugInput.removeAttribute('disabled');
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock2" viewBox="0 0 16 16">\n' +
                    '  <path fill-rule="evenodd" d="M8 0c1.07 0 2.041.42 2.759 1.104l.14.14.062.08a.5.5 0 0 1-.71.675l-.076-.066-.216-.205A3 3 0 0 0 5 4v2h6.5A2.5 2.5 0 0 1 14 8.5v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7z"/>\n' +
                    '</svg>';
            } else {
                slugInput.setAttribute('readonly', 'readonly');
                slugInput.setAttribute('disabled', 'disabled');
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16">\n' +
                    '  <path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/>\n' +
                    '</svg>';
            }
        };

        let timeout;

        nameInput.addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                updateSlug();
            }, 100);
        });

        if (lockButton) {
            lockButton.addEventListener('click', (event) => {
                event.preventDefault();
                toggleSlugModification(event.currentTarget);
            });
        }
    }
});
