const EasyCrudChoiceMaskHandler = function () {
  let self = this;



  self.handleChoiceMaskChange = function(choiceMaskMap, formWrapper, allMaskElements, value) {
    // Hide all mask elements initially
    allMaskElements.forEach((maskElement) => {
      const elementToHide = formWrapper.querySelector(`[name$="[${maskElement}]"]`);
      if (elementToHide) {
        elementToHide.closest('.field').style.display = 'none';
        elementToHide.closest('.field').querySelectorAll('[required="required"]').forEach(function (e){
          e.setAttribute("data-required", "required");
          e.removeAttribute("required");
        })
      }
    });
    // Show only the mask elements related to the selected choice value
    // ev.target.value is the selected choice value
    // Get the mask elements to show from the choiceMaskMap
    const fieldsToShow = choiceMaskMap[value];
    if(!fieldsToShow){
      return;
    }
    fieldsToShow.forEach((maskElement) => {
      const elementToShow = formWrapper.querySelector(`[name$="[${maskElement}]"]`);
      if (elementToShow) {
        elementToShow.closest('.field').style.display = '';
        elementToShow.querySelectorAll('[data-required="required"]')
          .forEach(function (e) {
            e.setAttribute("required", "required");
          })
      }
    });
  };

  // Find all choice mask fields
  const choiceMaskFields = document.querySelectorAll('[data-choice-mask]');
  choiceMaskFields.forEach((field) => {
    // Get closest form wrapper
    const formWrapper = field.closest('form');

    // Get field attr "data-choice-mask-map" that contains the mapping between choice values and mask elements
    const choiceMaskMapAttr = field.getAttribute('data-choice-mask-map');
    if (!choiceMaskMapAttr) {
      return;
    }
    // choiceMaskMapAttr is a JSON string, parse it to an object
    const choiceMaskMap = JSON.parse(choiceMaskMapAttr);

    // choiceMaskMap is a key value array where key is the choice value and value is an array of mask elements to show
    // We need to get all possible mask elements to hide them initially
    let allMaskElements = [];
    Object.values(choiceMaskMap).forEach((maskElements) => {
      allMaskElements = allMaskElements.concat(maskElements);
    });
    // Remove duplicates
    allMaskElements = [...new Set(allMaskElements)];

    // For each mapping, show/hide the related mask elements based on the selected choice value
    // Add event listener to all input radio inside the field
    // When the value changes, we need to update the mask elements visibility
    // The mask elements are forms fields with name as "%[maskElement]"
    const choiceInputs = field.querySelectorAll('input[type="radio"]');
    choiceInputs.forEach((input) => {
      input.addEventListener('change', (ev) => {
        self.handleChoiceMaskChange(choiceMaskMap, formWrapper, allMaskElements, ev.target.value);
      });
    });

    // On load, get field input checked value and update the mask elements visibility
    const checkedInput = field.querySelector('input[type="radio"]:checked');
    if (checkedInput) {
      self.handleChoiceMaskChange(choiceMaskMap, formWrapper, allMaskElements, checkedInput.value);
    }
  });
}

if (document.readyState == 'loading') {
  // still loading, wait for the event
  window.addEventListener('DOMContentLoaded', EasyCrudChoiceMaskHandler);
} else {
  EasyCrudChoiceMaskHandler();
}

document.addEventListener('collection-form-add', (event) => {
  EasyCrudChoiceMaskHandler();
});
document.addEventListener('collection-form-update', (event) => {
  EasyCrudChoiceMaskHandler();
});
document.addEventListener('collection-form-delete', (event) => {
  EasyCrudChoiceMaskHandler();
});

