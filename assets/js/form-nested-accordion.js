

window.addEventListener('DOMContentLoaded', () => {
  const initAccordion = function(target) {
    if (target
      .find('.ui.accordion').length > 0) {
      const id = target
        .find('.ui.accordion').data('accordion-id');
      const accordion = target
        .find('.ui.accordion');
      accordion.accordion('destroy');
      accordion
        .accordion({
          selector: {
            accordion: '.accordion',
            title: '.title',
            trigger: '.title',
            content: '.content',
          },
          animateChildren: false,
          duration: 0,
          namespace: id,
          name: id,
        });
    }
  };

  const collectionFirstItems = document.querySelectorAll("[data-form-collection=\"item\"]:first-child");
  if (collectionFirstItems.length > 0) {
    collectionFirstItems.forEach(function (item) {
      const observer = new MutationObserver(function(event) {
        setTimeout(function() {
          initAccordion($(event.target));
        },1);
      });

      observer.observe(item.closest('.collection-list'), {
        subtree: true,
        childList: true,
      });

    })
  }

  // init after default sylius script
  // look at $('.ui.accordion').accordion(); in admin-entry.js
  setTimeout(() => {
    document.querySelectorAll('[data-form-collection="item"]')
      .forEach(function (selector) {
        initAccordion($(selector));
      });
  },1);
});
