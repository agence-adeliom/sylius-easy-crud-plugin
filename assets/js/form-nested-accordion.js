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

  if (document.querySelector("[data-form-collection=\"item\"]")) {
    const observer = new MutationObserver(function() {
      setTimeout(function() {
        initAccordion($(event.target));
      },1);
    });

    observer.observe(document.querySelector("[data-form-collection=\"item\"]"), {
      subtree: true,
      childList: true,
    });
  }

  // init after default sylius script
  // look at $('.ui.accordion').accordion(); in admin-entry.js
  setTimeout(() => {
    $('[data-form-collection="item"]')
      .each(function () {
        initAccordion($(this));
      });
  },1);
});
