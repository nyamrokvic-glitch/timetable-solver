(function ($) {
  "use strict";

  if (!$) {
    return;
  }

  var backdropClass = "modal-backdrop";

  function getTarget($trigger) {
    var selector = $trigger.attr("data-target") || $trigger.attr("href");
    if (!selector || selector === "#") {
      return $();
    }
    return $(selector);
  }

  function closeOpenDropdowns() {
    $(".dropdown.open").removeClass("open");
  }

  function ensureBackdrop() {
    if ($("." + backdropClass).length) {
      return;
    }
    $('<div class="' + backdropClass + ' fade in"></div>').appendTo(document.body);
  }

  function removeBackdrop() {
    $("." + backdropClass).remove();
  }

  function showModal($modal) {
    if (!$modal.length) {
      return;
    }

    ensureBackdrop();
    $("body").addClass("modal-open");
    $modal.show().addClass("in");
    $modal.attr("aria-hidden", "false");
  }

  function hideModal($modal) {
    if (!$modal.length) {
      return;
    }

    $modal.hide().removeClass("in");
    $modal.attr("aria-hidden", "true");

    if (!$(".modal.in:visible").length) {
      $("body").removeClass("modal-open");
      removeBackdrop();
    }
  }

  $.fn.modal = function (action) {
    return this.each(function () {
      var $modal = $(this);
      var modalAction = action || "toggle";

      if (modalAction === "show") {
        showModal($modal);
      } else if (modalAction === "hide") {
        hideModal($modal);
      } else {
        if ($modal.hasClass("in") && $modal.is(":visible")) {
          hideModal($modal);
        } else {
          showModal($modal);
        }
      }
    });
  };

  $.fn.button = function (action) {
    return this.each(function () {
      var $btn = $(this);

      if (action === "loading") {
        if (!$btn.data("original-text")) {
          $btn.data("original-text", $btn.html());
        }
        var loadingText = $btn.attr("data-loading-text") || "Loading...";
        $btn.html(loadingText);
        $btn.prop("disabled", true).addClass("disabled");
      } else if (action === "reset") {
        var originalText = $btn.data("original-text");
        if (originalText) {
          $btn.html(originalText);
        }
        $btn.prop("disabled", false).removeClass("disabled");
      }
    });
  };

  $(document).on("click", "[data-toggle='dropdown']", function (event) {
    event.preventDefault();
    event.stopPropagation();

    var $dropdown = $(this).closest(".dropdown");
    var isOpen = $dropdown.hasClass("open");

    closeOpenDropdowns();

    if (!isOpen) {
      $dropdown.addClass("open");
    }
  });

  $(document).on("click", function () {
    closeOpenDropdowns();
  });

  $(document).on("click", "[data-toggle='collapse']", function (event) {
    event.preventDefault();
    var $target = getTarget($(this));

    if (!$target.length) {
      return;
    }

    var isMobileNavDrawer =
      $target.attr("id") === "main-nav" &&
      window.matchMedia("(max-width: 767px)").matches;

    $target.toggleClass("in");

    if (isMobileNavDrawer) {
      if ($target.hasClass("in")) {
        $("body").addClass("nav-drawer-open");
        $("#nav-overlay").addClass("in").removeClass("hidden");
      } else {
        $("body").removeClass("nav-drawer-open");
        $("#nav-overlay").removeClass("in").addClass("hidden");
      }
      return;
    }

    if ($target.hasClass("in")) {
      $target.show();
    } else {
      $target.hide();
    }
  });

  $(document).on("click", "[data-toggle='tab']", function (event) {
    event.preventDefault();
    var $trigger = $(this);
    var selector = $trigger.attr("href");
    if (!selector || selector.charAt(0) !== "#") {
      return;
    }

    var $tabList = $trigger.closest("ul, .tab-list");
    $tabList.find("[data-toggle='tab']").removeClass("active");
    $trigger.addClass("active");

    var $tabContainer = $(selector).closest(".tab-content");
    $tabContainer.find(".tab-pane").removeClass("active").hide();
    $(selector).addClass("active").show();
  });

  $(document).on("click", "[data-toggle='modal']", function (event) {
    event.preventDefault();
    showModal(getTarget($(this)));
  });

  $(document).on("click", "[data-dismiss='modal']", function (event) {
    event.preventDefault();
    hideModal($(this).closest(".modal"));
  });

  $(document).on("click", "[data-dismiss='alert']", function (event) {
    event.preventDefault();
    $(this).closest(".alert").remove();
  });

  $(document).on("click", "#nav-overlay", function () {
    $("#main-nav").removeClass("in");
    $("body").removeClass("nav-drawer-open");
    $("#nav-overlay").removeClass("in").addClass("hidden");
  });

  $(document).on("click", ".modal", function (event) {
    if ($(event.target).hasClass("modal")) {
      hideModal($(event.target));
    }
  });

  $(document).on("keydown", function (event) {
    if (event.key === "Escape") {
      hideModal($(".modal.in:visible").last());
      $("#main-nav").removeClass("in");
      $("body").removeClass("nav-drawer-open");
      $("#nav-overlay").removeClass("in").addClass("hidden");
    }
  });

  $(window).on("resize", function () {
    if (window.matchMedia("(min-width: 768px)").matches) {
      $("body").removeClass("nav-drawer-open");
      $("#nav-overlay").removeClass("in").addClass("hidden");
      $("#main-nav").removeClass("in").show();
    }
  });
})(window.jQuery);
