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
        button.innerHTML = '<i class="unlock icon"></i>';
      } else {
        slugInput.setAttribute('readonly', 'readonly');
        button.innerHTML = '<i class="lock icon"></i>';
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

