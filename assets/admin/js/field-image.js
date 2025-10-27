import $ from 'jquery';

const displayUploadedImage = function displayUploadedImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = (event) => {
      const image = $('.avatar', $(input).parent().parent());
      if (image.length > 0) {
        $(image).css("background-image", `url('${event.target.result}')`);
      }
    };

    reader.readAsDataURL(input.files[0]);
  }
};

$.fn.extend({
  previewUploadedImage(root) {
    $(root).on('change', 'input[type="file"]', function() {
      displayUploadedImage(this);
    });
  },
});

$(document).previewUploadedImage('[data-file]');
