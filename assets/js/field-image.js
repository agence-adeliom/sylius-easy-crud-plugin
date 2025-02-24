import $ from 'jquery';

const displayUploadedImage = function displayUploadedImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = (event) => {
      const image = $('.image', $(input).parent().parent());
console.log(image);
      if (image.length > 0) {
        image.attr('src', event.target.result);
      } else {
        const img = $('<img class="card-img-top img-thumbnail mb-3 image"/>');
        const div = $('<div class="card" style="width: 200px">');
        img.attr('src', event.target.result);
        div.prepend(img);
        $(input).parent().before(div);
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
