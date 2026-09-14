(function ($) {
  "use strict";

  function getField(target) {
    return $(document.getElementById(target));
  }

  function updatePreview($field) {
    var $container = $field.closest(".bnwp-media-field");
    var value = $.trim($field.val());
    var $preview = $container.find(".bnwp-media-preview");
    var $removeButton = $container.find(".bnwp-remove-media");

    $preview.toggleClass("is-hidden", !value);
    $removeButton.toggleClass("is-hidden", !value);
    $preview.find("img").attr("src", value);
  }

  $(document).on("click", ".bnwp-select-media", function (event) {
    event.preventDefault();

    var $field = getField($(this).data("target"));
    if (!$field.length) {
      return;
    }

    var frame = wp.media({
      title: bnwpMedia.frameTitle,
      button: { text: bnwpMedia.buttonText },
      library: { type: "image" },
      multiple: false,
    });

    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      $field.val(attachment.url).trigger("change");
    });

    frame.open();
  });

  $(document).on("click", ".bnwp-remove-media", function (event) {
    event.preventDefault();
    getField($(this).data("target")).val("").trigger("change");
  });

  $(document).on("input change", "[data-bnwp-media-url]", function () {
    updatePreview($(this));
  });
})(jQuery);
