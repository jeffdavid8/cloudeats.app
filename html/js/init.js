(function ($) {
  var $header = $(".header");
  var $primarySearchField = $("#index-search-field");

  // Init Materialize
  //M.AutoInit();
  /*
  $(".sidenav").sidenav({
    onOpenStart: function () {
      //$(this)[0].$el.addClass('shadow');
    },
    onCloseStart: function () {
      //$(this)[0].$el.removeClass('shadow');
    },
  });
*/
  $('.sidenav a[href="' + location.href + '"]')
    .parent()
    .addClass("active")
    .closest("ul.collapsible")
    .collapsible("open");

  $(".tooltipped").tooltip();

  window.addEventListener('scroll', () => {
    const navbar = document.getElementById('navbar');

    if (window.scrollY > 40) {
      document.body.classList.add('scrolled');
    } else {
      document.body.classList.remove('scrolled');
    }
  });

  // Add .scrolled to body if document scroll position is not 0
  if (window.scrollY > 0) {
    document.body.classList.add('scrolled');
  }

  const growingContent = document.getElementsByClassName("growingContent");

  function adjustHeight() {
    this.style.height = "auto"; // Reset height to recalculate
    this.style.height = this.scrollHeight + "px"; // Set height based on scrollHeight
  }

  for (let i = 0; i < growingContent.length; i++) {
    // Adjust height on initial load (if there's pre-existing content)
    growingContent[i].style.height = "auto"; // Reset height to recalculate
    growingContent[i].style.height = growingContent[i].scrollHeight + "px"; // Set height based
    // Attach listener to update the height
    growingContent[i].addEventListener("input", adjustHeight);
  }

  function updateFacebookLikeButton(url) {
    // Get the Like button element
    const likeButton = document.querySelector(".fb-like");

    if (likeButton) {
      // Update the data-href attribute with the new URL
      likeButton.setAttribute("data-href", url);

      // Check if the Facebook SDK has been initialized
      if (window.FB) {
        // Re-parse the DOM to render the new button
        window.FB.XFBML.parse();
      }
    }
  }

  // Example usage: Call this function after your dynamic content has loaded.
  // For a single page application (SPA), this would be inside your
  // routing logic or a callback after an AJAX request.
  // For a traditional server-rendered page, it could be called on window.onload.
  window.onload = function () {
    const currentPageUrl = window.location.href;
    updateFacebookLikeButton(currentPageUrl);
  };

  /*
   *  NightmodeButton
   */
  $(".nightModeTrigger, .nightModeTrigger .switch input").on(
    "click",
    function (e) {
      e.stopPropagation();
      var $this =
        $(this).prop("nodeName") == "input"
          ? $(this)
          : $(this).find(".switch input");

      $this.prop("checked", !$("body").hasClass("nightMode"));
      mb.toggleNightMode();
      return false;
    },
  );

  /*
   *  Primary Search Field
   */
  $primarySearchField
    .on("focus", function (e) {
      var $this = $(this);

      $this.closest(".primary-search-field").addClass("focus");

      if ($this.hasClass("submitted")) {
        $this.select();
        $this.on("change", function () {
          $(this).removeClass("submitted").off("change");
        });
      }
    })
    .on("blur", function () {
      $(this).closest(".primary-search-field").removeClass("focus");
    });

  /*
   *  Search Field Behavior
   */
  $(".scripture-search").each(function () {
    var $this = $(this);

    $this.on("keyup", function (e) {
      var searchString = $this.val();

      if (e.which == 13) {
        // Submit Search Request
        loading(1);
        $("body").addClass("submitted");
        $this.addClass("submitted").blur();
        $this.closest("form").submit();
        //searchScriptures(searchString);
      }
      if (e.which == 27) {
        $this.blur();
      }
    });
  });

  /*
   *  🔎
   */
  $("#index-search-btn").on("click", function (e) {
    var $searchField = $("#index-search-field");
    var searchString = $searchField.val();
    e.preventDefault();
    e.stopPropagation();

    if (!searchString.length) {
      $searchField.focus().select();
    } else {
      loading(1);
      $searchField.addClass("submitted");
      $searchField.closest("form").submit();
      //searchScriptures(searchString);
    }
  });

  /*
   *  Page link
   */
  $(".page_link").each(function () {
    $(this).attr('title', 'Copy share link to clipboard')
    $(this).on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var url = $(this).attr("href");
      if (!url.length || url == "#!") {
        url = window.location.href;
      }
      copyText(url);
      notify(
        '<i class="fas fa-copy"></i> &nbsp; Page link copied to clipboard',
      );
      return false;
    });
  });

  /*
   *  Results
   $('.results .keyword[data-key]:not([data-key=""]) .scriptures').each(function() {
      // Keyword highlighting
      var $this = $(this);
      var keyword = $this.closest('.keyword.result').attr('data-key');
      var words = keyword.split(' ');
      var html = $this.html();
      var query = '';
      var enew = '';
      var newe = '';
      $.each(words, function(index, word)
      {
         console.log(word);
         query = new RegExp("(\\b" + word + "\\b)", "gim");
         enew = html.replace(/(<span class="highlight">|<\/span>)/igm, "");
         newe = enew.replace(query, '<span class="highlight">$1</span>');
      });
      $this.html(html);
   });
   */

  $(".tabs").tabs({ swipeable: true });

  $(".collapsible").not(".expandable").collapsible();
  $(".collapsible.expandable").collapsible({
    accordion: false,
  });

  /*
   *
   */
  mb.toggleNightMode = function() {
    var $body = $("body");

    if ($body.hasClass("nightMode")) {
      $body.removeClass("nightMode");
      $body.addClass("dayMode");
    } else {
      $body.removeClass("dayMode");
      $body.addClass("nightMode");
    }

    var mode = $("body").hasClass("nightMode") ? "night" : "day";
    var package = {
      action: "toggle_night_mode",
      data: {
        day_night_mode: mode,
      },
    };

    $.ajax({
      url: "api.php",
      method: "POST",
      dataType: "json",
      data: JSON.stringify(package),
      success: function (data) {
        //console.log(data);
      },
      error: function (response) {
        //console.log('There was a problem with the api call');
        console.log(response);
      },
    });
  }

  /*
   *  Overlay
   */
  if ($.fn.overlay) {
    $(".overlay").overlay();
  }

  $("a.disabled").on("click", function (e) {
    if ($(this).hasClass("disabled")) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  });
})(jQuery);
